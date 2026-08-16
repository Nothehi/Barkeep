<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestCreated;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->version = GameVersion::factory()->nextFor($this->game)->create();
});

function planPlaytest(array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(
        '/api/v1/workspaces/studio/games/bears-and-bridges/playtests',
        array_merge([
            'game_version_id' => test()->version->id,
            'title' => 'First-player advantage',
            'objective' => 'Determine whether the first player wins too often.',
        ], $payload),
    );
}

it('plans a playtest against a version of a game', function () {
    planPlaytest(['hypothesis' => 'Going first is worth about a turn.'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'First-player advantage')
        ->assertJsonPath('data.objective', 'Determine whether the first player wins too often.')
        ->assertJsonPath('data.hypothesis', 'Going first is worth about a turn.')
        ->assertJsonPath('data.status', PlaytestStatus::Planned->value)
        ->assertJsonPath('data.game_id', $this->game->id)
        ->assertJsonPath('data.game_version_id', $this->version->id)
        ->assertJsonPath('data.created_by', $this->designer->id);
});

it('accepts a playtest with no hypothesis, because exploratory tests are real', function () {
    planPlaytest(['hypothesis' => null])
        ->assertCreated()
        ->assertJsonPath('data.hypothesis', null);
});

it('records when the playtest is meant to happen', function () {
    planPlaytest(['planned_at' => '2026-09-01T18:30:00+00:00'])
        ->assertCreated()
        ->assertJsonPath('data.status', PlaytestStatus::Planned->value);

    expect(Playtest::query()->sole()->planned_at?->toDateString())->toBe('2026-09-01');
});

it('names the signed in account as the planner, whatever the body says', function () {
    $someoneElse = User::factory()->create();

    planPlaytest(['created_by' => $someoneElse->id])
        ->assertCreated()
        ->assertJsonPath('data.created_by', $this->designer->id);
});

/**
 * The module's central invariant. A playtest whose version came from another
 * game is not a validation nicety — it is a record that reads perfectly and
 * describes an evening nobody had.
 */
it('refuses a version that belongs to a different game', function () {
    $otherGame = Game::factory()->inWorkspace($this->workspace)->withSlug('other-game')->active()->create();
    $otherVersion = GameVersion::factory()->nextFor($otherGame)->create();

    planPlaytest(['game_version_id' => $otherVersion->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');

    expect(Playtest::query()->count())->toBe(0);
});

it('refuses a version from another workspace entirely', function () {
    $outsider = User::factory()->create();
    $elsewhere = Workspace::factory()->ownedBy($outsider)->withSlug('rivals')->create();
    $theirGame = Game::factory()->inWorkspace($elsewhere)->active()->create();
    $theirVersion = GameVersion::factory()->nextFor($theirGame)->create();

    planPlaytest(['game_version_id' => $theirVersion->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');
});

it('refuses a version id that names nothing', function () {
    planPlaytest(['game_version_id' => 'not-a-uuid'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('game_version_id');
});

it('requires a version, a title and an objective', function (string $field) {
    planPlaytest([$field => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor($field);
})->with(['game_version_id', 'title', 'objective']);

it('refuses an objective too short to interpret later', function () {
    planPlaytest(['objective' => 'test it'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('objective');
});

it('announces the version under test, not just the game', function () {
    Event::fake([PlaytestCreated::class]);

    planPlaytest()->assertCreated();

    Event::assertDispatched(
        PlaytestCreated::class,
        fn (PlaytestCreated $event) => $event->gameId === $this->game->id
            && $event->gameVersionId === $this->version->id
            && $event->createdBy === $this->designer->id,
    );
});

it('lists a game\'s playtests, most recently planned first', function () {
    Playtest::factory()->forGame($this->game)->titled('Older')->create(['planned_at' => now()->subWeek()]);
    Playtest::factory()->forGame($this->game)->titled('Newer')->create(['planned_at' => now()]);

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/playtests')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Newer')
        ->assertJsonPath('data.1.title', 'Older');
});

it('filters the list by status', function () {
    Playtest::factory()->forGame($this->game)->titled('Running')->inProgress()->create();
    Playtest::factory()->forGame($this->game)->titled('Done')->completed()->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/playtests?status=completed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Done');
});

it('searches titles, objectives and hypotheses', function (string $term) {
    Playtest::factory()->forGame($this->game)->titled('Scoring clarity')->create([
        'objective' => 'Find out whether the endgame scoring reads clearly.',
        'hypothesis' => 'Players will miscount the majority bonus.',
    ]);
    Playtest::factory()->forGame($this->game)->titled('Something else')->create([
        'objective' => 'Watch the opening turns.',
        'hypothesis' => null,
    ]);

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/playtests?search={$term}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Scoring clarity');
})->with([
    'the title' => 'clarity',
    'the objective' => 'endgame',
    'the hypothesis' => 'majority',
    'regardless of case' => 'ENDGAME',
]);

it('does not let a playtest be planned against an archived game', function () {
    $archived = Game::factory()->inWorkspace($this->workspace)->withSlug('shelved')->archived()->create();
    $version = GameVersion::factory()->nextFor($archived)->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/shelved/playtests', [
            'game_version_id' => $version->id,
            'title' => 'Too late',
            'objective' => 'Find out something about a shelved project.',
        ])
        ->assertForbidden();
});
