<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Events\GameArchived;
use Modules\GameDesign\Domain\Events\GameDesignPhaseChanged;
use Modules\GameDesign\Domain\Events\GameStatusChanged;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
});

/**
 * Build a game sitting at a particular point in its lifecycle.
 */
function gameAt(GameStatus $status): Game
{
    return Game::factory()
        ->inWorkspace(test()->workspace)
        ->withSlug('bears-and-bridges')
        ->withStatus($status)
        ->create();
}

function changeStatus(GameStatus $to)
{
    return test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/status', [
            'status' => $to->value,
        ]);
}

it('walks a game through its whole lifecycle', function () {
    gameAt(GameStatus::Draft);

    changeStatus(GameStatus::Active)->assertOk()->assertJsonPath('data.status', 'active');
    changeStatus(GameStatus::OnHold)->assertOk()->assertJsonPath('data.status', 'on_hold');
    changeStatus(GameStatus::Active)->assertOk()->assertJsonPath('data.status', 'active');
    changeStatus(GameStatus::Completed)->assertOk()->assertJsonPath('data.status', 'completed');

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/archive')
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('allows the transitions the matrix permits', function (GameStatus $from, GameStatus $to) {
    gameAt($from);

    changeStatus($to)->assertOk()->assertJsonPath('data.status', $to->value);
})->with([
    'draft to active' => [GameStatus::Draft, GameStatus::Active],
    'active to on hold' => [GameStatus::Active, GameStatus::OnHold],
    'on hold to active' => [GameStatus::OnHold, GameStatus::Active],
    'active to completed' => [GameStatus::Active, GameStatus::Completed],
]);

/**
 * The refusals matter more than the permissions. A game does not skip from
 * draft to completed, and a game that was parked does not become finished
 * without first being picked back up.
 */
it('refuses the transitions the matrix forbids', function (GameStatus $from, GameStatus $to) {
    $game = gameAt($from);

    changeStatus($to)->assertStatus(409);

    expect($game->fresh()?->status)->toBe($from);
})->with([
    'draft to on hold' => [GameStatus::Draft, GameStatus::OnHold],
    'draft to completed' => [GameStatus::Draft, GameStatus::Completed],
    'on hold to completed' => [GameStatus::OnHold, GameStatus::Completed],
    'completed to on hold' => [GameStatus::Completed, GameStatus::OnHold],
    'completed to active' => [GameStatus::Completed, GameStatus::Active],
    'completed to draft' => [GameStatus::Completed, GameStatus::Draft],
    'active to draft' => [GameStatus::Active, GameStatus::Draft],
]);

/**
 * Archival is not reachable through the status endpoint at all. It has its
 * own route because it cannot be undone, and an irreversible move should not
 * be one field value away from a reversible one.
 */
it('will not archive a game through the status endpoint', function () {
    $game = gameAt(GameStatus::Active);

    changeStatus(GameStatus::Archived)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    expect($game->fresh()?->status)->toBe(GameStatus::Active);
});

it('treats a move to the status a game is already in as a no-op', function () {
    Event::fake([GameStatusChanged::class]);

    gameAt(GameStatus::Active);

    changeStatus(GameStatus::Active)->assertOk()->assertJsonPath('data.status', 'active');

    Event::assertNotDispatched(GameStatusChanged::class);
});

it('rejects a status that names nothing', function () {
    gameAt(GameStatus::Draft);

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/status', ['status' => 'shipped'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('announces both ends of a lifecycle move', function () {
    Event::fake([GameStatusChanged::class]);

    $game = gameAt(GameStatus::Draft);

    changeStatus(GameStatus::Active)->assertOk();

    Event::assertDispatched(
        GameStatusChanged::class,
        fn (GameStatusChanged $event) => $event->gameId === $game->id
            && $event->workspaceId === test()->workspace->id
            && $event->changedBy === test()->designer->id
            && $event->from === GameStatus::Draft
            && $event->to === GameStatus::Active,
    );
});

it('announces an archival as its own event as well as a status change', function () {
    Event::fake([GameStatusChanged::class, GameArchived::class]);

    $game = gameAt(GameStatus::Active);

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/archive')
        ->assertOk();

    Event::assertDispatched(GameStatusChanged::class);
    Event::assertDispatched(
        GameArchived::class,
        fn (GameArchived $event) => $event->gameId === $game->id
            && $event->archivedBy === test()->designer->id,
    );
});

/**
 * The interface is driven by the matrix rather than by its own copy of it, so
 * the game itself has to say which moves it can make.
 */
it('offers the game its own legal moves and no others', function () {
    gameAt(GameStatus::Active);

    $response = test()->actingAs(test()->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges')
        ->assertOk();

    $offered = array_column($response->json('data.available_transitions'), 'status');

    expect($offered)->toEqualCanonicalizing(['on_hold', 'completed']);
});

it('offers an archived game no moves at all', function () {
    gameAt(GameStatus::Archived);

    test()->actingAs(test()->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges')
        ->assertOk()
        ->assertJsonPath('data.available_transitions', []);
});

/**
 * Status and design phase are independent. A game can be parked in the middle
 * of playtesting, and neither value constrains the other.
 */
it('moves the design phase without touching the status', function () {
    $game = gameAt(GameStatus::OnHold);

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/design-phase', [
            'design_phase' => 'playtesting',
        ])
        ->assertOk()
        ->assertJsonPath('data.design_phase', 'playtesting')
        ->assertJsonPath('data.status', 'on_hold');

    expect($game->fresh()?->status)->toBe(GameStatus::OnHold);
});

/**
 * Designing a board game is not a pipeline. Dropping back from playtesting to
 * prototyping is the normal thing to do when the core loop turns out to be
 * broken, and forbidding it would describe a process nobody follows.
 */
it('lets the design phase move backwards', function () {
    gameAt(GameStatus::Active)->forceFill(['design_phase' => 'playtesting'])->save();

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/design-phase', [
            'design_phase' => 'prototyping',
        ])
        ->assertOk()
        ->assertJsonPath('data.design_phase', 'prototyping');
});

it('announces a design phase move', function () {
    Event::fake([GameDesignPhaseChanged::class]);

    gameAt(GameStatus::Draft);

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/design-phase', [
            'design_phase' => 'concept',
        ])
        ->assertOk();

    Event::assertDispatched(
        GameDesignPhaseChanged::class,
        fn (GameDesignPhaseChanged $event) => $event->from->value === 'idea'
            && $event->to->value === 'concept',
    );
});

it('rejects a design phase that names nothing', function () {
    gameAt(GameStatus::Draft);

    test()->actingAs(test()->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/design-phase', [
            'design_phase' => 'kickstarter',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('design_phase');
});

it('moves a game through its lifecycle from the web screens', function () {
    gameAt(GameStatus::Draft);

    $this->actingAs($this->designer)
        ->from(route('games.show', ['studio', 'bears-and-bridges']))
        ->post(route('games.status', ['studio', 'bears-and-bridges']), ['status' => 'active'])
        ->assertRedirect(route('games.show', ['studio', 'bears-and-bridges']));

    expect(Game::query()->where('slug', 'bears-and-bridges')->sole()->status)
        ->toBe(GameStatus::Active);
});
