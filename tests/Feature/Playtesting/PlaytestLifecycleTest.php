<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestCancelled;
use Modules\Playtesting\Domain\Events\PlaytestCompleted;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->playtest = Playtest::factory()->forGame($this->game)->createdBy($this->designer)->create();

    $this->url = "/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$this->playtest->id}";
});

it('completes a playtest the designer says is answered', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->url}/complete", ['conclusion' => 'The advantage is real but small.'])
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestStatus::Completed->value)
        ->assertJsonPath('data.conclusion', 'The advantage is real but small.');

    expect($this->playtest->fresh()->completed_at)->not->toBeNull();
});

it('completes without a conclusion, to be written up later', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->url}/complete")
        ->assertOk()
        ->assertJsonPath('data.conclusion', null);
});

/**
 * The one evidence rule the lifecycle enforces. An investigation with no
 * sessions did not happen, whatever was concluded from it.
 */
it('refuses to complete a playtest that never ran a session', function () {
    $this->actingAs($this->designer)
        ->postJson("{$this->url}/complete")
        ->assertStatus(409);

    expect($this->playtest->fresh()->status)->toBe(PlaytestStatus::Planned);
});

/**
 * Deliberately *not* required: that the sessions completed. A playtest whose
 * every sitting was abandoned still taught the designer something.
 */
it('completes a playtest whose only session was cancelled', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->cancelled()->create();

    $this->actingAs($this->designer)
        ->postJson("{$this->url}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestStatus::Completed->value);
});

it('cancels a playtest that is not worth running', function () {
    $this->actingAs($this->designer)
        ->postJson("{$this->url}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', PlaytestStatus::Cancelled->value);
});

it('cancels a playtest without needing a session first', function () {
    expect($this->playtest->sessions()->count())->toBe(0);

    $this->actingAs($this->designer)->postJson("{$this->url}/cancel")->assertOk();
});

/**
 * A cancelled playtest keeps whatever really happened. Blanking two completed
 * sittings to tidy up a status would destroy evidence.
 */
it('leaves the sessions of a cancelled playtest alone', function () {
    $session = PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();

    $this->actingAs($this->designer)->postJson("{$this->url}/cancel")->assertOk();

    expect($session->fresh())->not->toBeNull()
        ->and($session->fresh()->status->value)->toBe('completed');
});

it('refuses to reopen a finished playtest', function (string $state, string $action) {
    $playtest = Playtest::factory()->forGame($this->game)->withStatus(PlaytestStatus::from($state))->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$playtest->id}/{$action}")
        ->assertForbidden();
})->with([
    'completed cannot complete again' => ['completed', 'complete'],
    'completed cannot be cancelled' => ['completed', 'cancel'],
    'cancelled cannot complete' => ['cancelled', 'complete'],
    'cancelled cannot cancel again' => ['cancelled', 'cancel'],
]);

it('announces completion with how much evidence there was', function () {
    Event::fake([PlaytestCompleted::class]);

    PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->count(3)->create();

    $this->actingAs($this->designer)->postJson("{$this->url}/complete")->assertOk();

    Event::assertDispatched(
        PlaytestCompleted::class,
        fn (PlaytestCompleted $event) => $event->playtestId === $this->playtest->id
            && $event->gameVersionId === $this->playtest->game_version_id
            && $event->sessionCount === 3,
    );
});

it('announces cancellation as a different fact from completion', function () {
    Event::fake([PlaytestCancelled::class, PlaytestCompleted::class]);

    $this->actingAs($this->designer)->postJson("{$this->url}/cancel")->assertOk();

    Event::assertDispatched(PlaytestCancelled::class);
    Event::assertNotDispatched(PlaytestCompleted::class);
});

it('updates a playtest that is still open', function () {
    $this->actingAs($this->designer)
        ->patchJson($this->url, [
            'title' => 'First-player advantage, take two',
            'objective' => 'Determine whether seating order still decides the game.',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'First-player advantage, take two');
});

/**
 * The freeze that makes a playtest record worth keeping: once it is over, the
 * question it asked cannot be rewritten to match the answer.
 */
it('refuses to rewrite the plan of a completed playtest', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    $this->actingAs($this->designer)->postJson("{$this->url}/complete")->assertOk();

    $this->actingAs($this->designer)
        ->patchJson($this->url, ['objective' => 'Something we can definitely prove we found.'])
        ->assertForbidden();
});

/**
 * The exception to that freeze, and the reason it is an exception: conclusions
 * are written days later, once somebody has read back through the notes.
 */
it('still accepts a conclusion after the playtest is completed', function () {
    PlaytestSession::factory()->forPlaytest($this->playtest)->completed()->create();
    $this->actingAs($this->designer)->postJson("{$this->url}/complete")->assertOk();

    $this->actingAs($this->designer)
        ->patchJson($this->url, ['conclusion' => 'Partially supported. The advantage is smaller than expected.'])
        ->assertOk()
        ->assertJsonPath('data.conclusion', 'Partially supported. The advantage is smaller than expected.');
});

it('refuses a conclusion on a cancelled playtest, which concluded nothing', function () {
    $this->actingAs($this->designer)->postJson("{$this->url}/cancel")->assertOk();

    $this->actingAs($this->designer)
        ->patchJson($this->url, ['conclusion' => 'We learned a lot really.'])
        ->assertForbidden();
});

it('retargets a planned playtest at a newer version', function () {
    $newer = GameVersion::factory()->nextFor($this->game)->create();

    $this->actingAs($this->designer)
        ->patchJson($this->url, ['game_version_id' => $newer->id])
        ->assertOk()
        ->assertJsonPath('data.game_version_id', $newer->id);
});

it('will not retarget a playtest at another game\'s version', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other-game')->active()->create();
    $theirs = GameVersion::factory()->nextFor($otherGame)->create();

    $this->actingAs($this->designer)
        ->patchJson($this->url, ['game_version_id' => $theirs->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');
});

it('offers only the moves the playtest can actually make', function () {
    $this->actingAs($this->designer)
        ->getJson($this->url)
        ->assertOk()
        ->assertJsonPath('data.available_transitions.0.status', 'completed')
        ->assertJsonPath('data.available_transitions.1.status', 'cancelled');
});

it('offers no moves on a finished playtest', function () {
    $done = Playtest::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/playtests/{$done->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data.available_transitions');
});
