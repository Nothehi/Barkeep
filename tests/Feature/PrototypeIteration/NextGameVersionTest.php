<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Events\GameVersionCreated;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Events\IterationCompleted;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Section 30 and section 48: the design loop closes with a person deciding, never with a side effect.
 *
 * Completing an iteration does not cut a new game version. Most cycles do not produce a new design state
 * — three iterations of tuning a cost curve are one design change between them — and a platform that cut
 * one per cycle would turn the version numbers into a count of button presses.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
});

it('does not cut a game version when an iteration completes', function () {
    $iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $before = GameVersion::query()->where('game_id', $this->game->id)->count();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/complete", [
            'outcome' => 'success',
            'summary' => 'Downtime fell by about a fifth and the table stayed engaged.',
        ])
        ->assertOk();

    expect(GameVersion::query()->where('game_id', $this->game->id)->count())->toBe($before);
});

it('dispatches no version event when an iteration completes', function () {
    Event::fake([GameVersionCreated::class, IterationCompleted::class]);

    $iteration = Iteration::factory()->forGame($this->game)->inProgress()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/complete", [
            'outcome' => 'success',
            'summary' => 'Downtime fell by about a fifth and the table stayed engaged.',
        ])
        ->assertOk();

    Event::assertDispatched(IterationCompleted::class);
    Event::assertNotDispatched(GameVersionCreated::class);
});

it('cuts the next design version on the designer\'s explicit say-so', function () {
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();
    $highest = (int) GameVersion::query()->where('game_id', $this->game->id)->max('version_number');

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version", [
            'name' => 'Simultaneous combat',
            'description' => 'Reaction phase removed following iteration 12.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.version_number', $highest + 1)
        ->assertJsonPath('data.name', 'Simultaneous combat')
        ->assertJsonPath('data.game_id', $this->game->id);
});

/**
 * GameDesign owns the version: it allocates the number, applies its own rules and announces it. This
 * module supplied the occasion and nothing else.
 */
it('lets GameDesign allocate the number and announce the version', function () {
    Event::fake([GameVersionCreated::class]);

    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version")
        ->assertCreated();

    Event::assertDispatched(fn (GameVersionCreated $event): bool => $event->gameId === $this->game->id
        && $event->createdBy === $this->designer->id);
});

it('ignores a version number the caller tries to supply', function () {
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();
    $highest = (int) GameVersion::query()->where('game_id', $this->game->id)->max('version_number');

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version", [
            'version_number' => 999,
        ])
        ->assertCreated()
        ->assertJsonPath('data.version_number', $highest + 1);
});

it('accepts the action with nothing to say about the new version', function () {
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version")
        ->assertCreated()
        ->assertJsonPath('data.name', null);
});

/**
 * A version cut from an open cycle would claim the design had moved on the strength of conclusions nobody
 * had reached — and since an open cycle can still change, the claim might describe abandoned work.
 */
it('refuses to cut a version from a cycle that has not finished', function (string $state) {
    $iteration = $state === 'planned'
        ? Iteration::factory()->forGame($this->game)->create()
        : Iteration::factory()->forGame($this->game)->inProgress()->create();

    $before = GameVersion::query()->where('game_id', $this->game->id)->count();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version")
        ->assertForbidden();

    expect(GameVersion::query()->where('game_id', $this->game->id)->count())->toBe($before);
})->with(['planned', 'in_progress']);

it('cuts a version from a cancelled cycle, because that is still a conclusion', function () {
    $iteration = Iteration::factory()->forGame($this->game)->cancelled()->create();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version")
        ->assertCreated();
});

it('refuses to cut a version once the game is archived', function () {
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version")
        ->assertForbidden();
});

it('refuses to cut a version from another studio\'s cycle', function () {
    $outsider = User::factory()->create();
    $iteration = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($outsider)
        ->postJson("/api/v1/workspaces/studio/games/bears/iterations/{$iteration->id}/game-version")
        ->assertNotFound();
});

it('tells the client whether the action is available', function () {
    $open = Iteration::factory()->forGame($this->game)->inProgress()->create();
    $closed = Iteration::factory()->forGame($this->game)->completed()->create();

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$open->id}")
        ->assertOk()
        ->assertJsonPath('data.permissions.canCreateGameVersion', false);

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears/iterations/{$closed->id}")
        ->assertOk()
        ->assertJsonPath('data.permissions.canCreateGameVersion', true);
});
