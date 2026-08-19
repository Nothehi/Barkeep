<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Two studios, each with a game, a prototype and a cycle, and neither able to see anything of the
 * other's.
 *
 * The chain being tested is the whole security model of the module:
 *
 *     account → workspace membership → game access → prototype | iteration → child records
 *
 * Every segment of every URL is resolved through the one before it, so a caller who guesses a valid uuid
 * still fails at the point where that record does not belong to what the rest of the address says it
 * does. Where a record is hidden rather than refused, the response is a 404 — a prototype id must not
 * confirm that a game exists to somebody who was not allowed to know.
 */
beforeEach(function () {
    $this->ours = User::factory()->create();
    $this->theirs = User::factory()->create();

    $this->ourWorkspace = Workspace::factory()->ownedBy($this->ours)->withSlug('studio')->create();
    $this->theirWorkspace = Workspace::factory()->ownedBy($this->theirs)->withSlug('rivals')->create();

    $this->ourGame = Game::factory()->inWorkspace($this->ourWorkspace)->withSlug('ours')->active()->create();
    $this->theirGame = Game::factory()->inWorkspace($this->theirWorkspace)->withSlug('theirs')->active()->create();

    $this->ourPrototype = Prototype::factory()->forGame($this->ourGame)->named('Ours')->create();
    $this->theirPrototype = Prototype::factory()->forGame($this->theirGame)->named('Theirs')->create();

    /*
     * The cycles are built against these prototypes' own builds rather than through `forGame()`, which
     * would cut a second prototype per game. Each studio ends up with exactly one prototype, which is
     * what the counts below are about.
     */
    $this->ourBuild = PrototypeVersion::factory()->nextFor($this->ourPrototype)->create();
    $this->theirBuild = PrototypeVersion::factory()->nextFor($this->theirPrototype)->create();

    $this->ourIteration = Iteration::factory()
        ->forPrototypeVersion($this->ourBuild)
        ->inProgress()
        ->titled('Our cycle')
        ->create(['created_by' => $this->ours->id]);

    $this->theirIteration = Iteration::factory()
        ->forPrototypeVersion($this->theirBuild)
        ->inProgress()
        ->titled('Their cycle')
        ->create(['created_by' => $this->theirs->id]);
});

it('shows each studio only its own prototypes', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/ours/prototypes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Ours');

    $this->actingAs($this->theirs)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/prototypes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Theirs');
});

it('shows each studio only its own cycles', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/ours/iterations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Our cycle');
});

it('hides another studio\'s prototype and iteration lists entirely', function (string $path) {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/'.$path)
        ->assertNotFound();
})->with(['prototypes', 'iterations']);

it('404s a prototype reached through its own studio\'s address', function () {
    $this->actingAs($this->ours)
        ->getJson("/api/v1/workspaces/rivals/games/theirs/prototypes/{$this->theirPrototype->id}")
        ->assertNotFound();
});

/**
 * The reverse-lookup attempt: a valid id from elsewhere, pasted into an address the caller *can* reach.
 * It fails at resolution rather than at a policy, which is why the nesting exists.
 */
it('404s another studio\'s prototype id inside our own address', function () {
    $this->actingAs($this->ours)
        ->getJson("/api/v1/workspaces/studio/games/ours/prototypes/{$this->theirPrototype->id}")
        ->assertNotFound();
});

it('404s another studio\'s iteration id inside our own address', function () {
    $this->actingAs($this->ours)
        ->getJson("/api/v1/workspaces/studio/games/ours/iterations/{$this->theirIteration->id}")
        ->assertNotFound();
});

it('404s another studio\'s prototype version through our own prototype', function () {
    $theirSecondBuild = PrototypeVersion::factory()->nextFor($this->theirPrototype)->create();

    $this->actingAs($this->ours)
        ->getJson("/api/v1/workspaces/studio/games/ours/prototypes/{$this->ourPrototype->id}/versions/{$theirSecondBuild->version_number}")
        ->assertNotFound();
});

it('404s another studio\'s change through our own cycle', function () {
    $theirChange = DesignChange::factory()->forIteration($this->theirIteration)->create();

    $this->actingAs($this->ours)
        ->patchJson(
            "/api/v1/workspaces/studio/games/ours/iterations/{$this->ourIteration->id}/changes/{$theirChange->id}",
            ['title' => 'Hijacked', 'reason' => 'Trying to reach across a boundary.'],
        )
        ->assertNotFound();

    expect($theirChange->refresh()->title)->not->toBe('Hijacked');
});

it('404s another studio\'s decision through our own cycle', function () {
    $theirDecision = DesignDecision::factory()->forIteration($this->theirIteration)->create();

    $this->actingAs($this->ours)
        ->postJson(
            "/api/v1/workspaces/studio/games/ours/iterations/{$this->ourIteration->id}/decisions/{$theirDecision->id}/accept",
        )
        ->assertNotFound();

    expect($theirDecision->refresh()->status->value)->toBe('proposed');
});

it('refuses to start a prototype in another studio\'s game', function () {
    $theirVersion = GameVersion::query()
        ->where('game_id', $this->theirGame->id)
        ->firstOrFail();

    $this->actingAs($this->ours)
        ->postJson('/api/v1/workspaces/rivals/games/theirs/prototypes', [
            'game_version_id' => $theirVersion->id,
            'name' => 'Uninvited',
        ])
        ->assertNotFound();

    expect(Prototype::query()->where('game_id', $this->theirGame->id)->count())->toBe(1);
});

it('refuses every write on another studio\'s cycle', function (string $method, string $path) {
    $url = "/api/v1/workspaces/rivals/games/theirs/iterations/{$this->theirIteration->id}".$path;

    $this->actingAs($this->ours)->json($method, $url, [
        'title' => 'Uninvited',
        'reason' => 'Trying to write into another studio\'s history.',
        'outcome' => 'success',
        'summary' => 'Claiming somebody else\'s work went well.',
    ])->assertNotFound();
})->with([
    ['patch', ''],
    ['post', '/start'],
    ['post', '/complete'],
    ['post', '/cancel'],
    ['post', '/changes'],
]);

/**
 * Membership is what grants access, not ownership. A member of the studio sees and works on its design
 * record exactly as the owner does — designing is the work, and gating it on administration would be a
 * strange shape for a design tool.
 */
it('lets any member of the studio read and record design work', function () {
    $member = User::factory()->create();

    $this->ourWorkspace->members()->create([
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->getJson('/api/v1/workspaces/studio/games/ours/iterations')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($member)
        ->postJson("/api/v1/workspaces/studio/games/ours/iterations/{$this->ourIteration->id}/changes", [
            'title' => 'Reduced starting resources',
            'reason' => 'Five made the opening turn a formality.',
        ])
        ->assertCreated();
});

it('turns away an account with no session at all', function () {
    $this->getJson('/api/v1/workspaces/studio/games/ours/iterations')->assertUnauthorized();
});
