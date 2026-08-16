<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Two studios, each with a game, a playtest and a session, and neither able to
 * see anything of the other's.
 *
 * The chain being tested is the whole security model of the module:
 *
 *     account → workspace membership → game access → playtest → session
 *
 * Every segment of every URL is resolved through the one before it, so a
 * caller who guesses a valid uuid still fails at the point where that record
 * does not belong to what the rest of the address says it does.
 */
beforeEach(function () {
    $this->ours = User::factory()->create();
    $this->theirs = User::factory()->create();

    $this->ourWorkspace = Workspace::factory()->ownedBy($this->ours)->withSlug('studio')->create();
    $this->theirWorkspace = Workspace::factory()->ownedBy($this->theirs)->withSlug('rivals')->create();

    $this->ourGame = Game::factory()->inWorkspace($this->ourWorkspace)->withSlug('ours')->active()->create();
    $this->theirGame = Game::factory()->inWorkspace($this->theirWorkspace)->withSlug('theirs')->active()->create();

    $this->ourPlaytest = Playtest::factory()->forGame($this->ourGame)->titled('Our test')->create();
    $this->theirPlaytest = Playtest::factory()->forGame($this->theirGame)->titled('Their test')->create();

    $this->theirSession = PlaytestSession::factory()->forPlaytest($this->theirPlaytest)->inProgress()->create();
});

it('shows each studio only its own playtests', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/ours/playtests')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Our test');

    $this->actingAs($this->theirs)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/playtests')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Their test');
});

it('hides another studio\'s playtest list entirely', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/playtests')
        ->assertNotFound();
});

it('hides another studio\'s playtest', function () {
    $this->actingAs($this->ours)
        ->getJson("/api/v1/workspaces/rivals/games/theirs/playtests/{$this->theirPlaytest->id}")
        ->assertNotFound();
});

/**
 * The interesting attack, and the one nesting defeats: naming a playtest you
 * cannot reach under a game you *can*. The playtest is resolved through the
 * game, so it is not there to be found.
 */
it('cannot reach another studio\'s playtest through a game it does own', function () {
    $this->actingAs($this->ours)
        ->getJson("/api/v1/workspaces/studio/games/ours/playtests/{$this->theirPlaytest->id}")
        ->assertNotFound();
});

it('cannot act on another studio\'s playtest through its own game', function (string $action) {
    $this->actingAs($this->ours)
        ->postJson("/api/v1/workspaces/studio/games/ours/playtests/{$this->theirPlaytest->id}/{$action}")
        ->assertNotFound();
})->with(['complete', 'cancel']);

it('cannot reach another studio\'s session through its own playtest', function () {
    $this->actingAs($this->ours)
        ->getJson(
            "/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}"
            ."/sessions/{$this->theirSession->id}",
        )
        ->assertNotFound();
});

it('cannot record evidence into another studio\'s session', function (string $resource, array $payload) {
    $this->actingAs($this->ours)
        ->postJson(
            "/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}"
            ."/sessions/{$this->theirSession->id}/{$resource}",
            $payload,
        )
        ->assertNotFound();
})->with([
    'participants' => ['participants', ['display_name' => 'Trespasser']],
    'observations' => ['observations', ['content' => 'Peeking at their game.']],
    'feedback' => ['feedback', ['content' => 'Peeking at their game.']],
]);

it('leaves another studio\'s session untouched after every attempt', function () {
    $this->actingAs($this->ours)
        ->postJson(
            "/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}"
            ."/sessions/{$this->theirSession->id}/participants",
            ['display_name' => 'Trespasser'],
        )
        ->assertNotFound();

    expect(PlaytestParticipant::query()->count())->toBe(0)
        ->and(PlaytestObservation::query()->count())->toBe(0)
        ->and(PlaytestFeedback::query()->count())->toBe(0);
});

it('cannot start, complete or cancel another studio\'s session', function (string $action) {
    $this->actingAs($this->ours)
        ->postJson(
            "/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}"
            ."/sessions/{$this->theirSession->id}/{$action}",
        )
        ->assertNotFound();
})->with(['start', 'complete', 'cancel']);

it('will not plan a playtest inside a workspace the caller does not belong to', function () {
    $version = $this->theirGame->versions()->first()
        ?? GameVersion::factory()->nextFor($this->theirGame)->create();

    $this->actingAs($this->ours)
        ->postJson('/api/v1/workspaces/rivals/games/theirs/playtests', [
            'game_version_id' => $version->id,
            'title' => 'Industrial espionage',
            'objective' => 'Find out how their game works from the inside.',
        ])
        ->assertNotFound();

    expect(Playtest::query()->count())->toBe(2);
});

/**
 * Membership is what opens the door, and it opens it fully: every member of a
 * workspace can work on every playtest in it, which is what a shared studio
 * actually looks like.
 */
it('lets a teammate read and record against the studio\'s playtests', function () {
    $teammate = User::factory()->create();

    $this->ourWorkspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($teammate)
        ->getJson("/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}")
        ->assertOk();

    $this->actingAs($teammate)
        ->postJson("/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}/sessions")
        ->assertCreated();
});

it('turns away a caller with no session at all', function () {
    $this->getJson("/api/v1/workspaces/studio/games/ours/playtests/{$this->ourPlaytest->id}")
        ->assertUnauthorized();
});

it('does not choke on a playtest id that is not a uuid', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/ours/playtests/latest')
        ->assertNotFound();
});
