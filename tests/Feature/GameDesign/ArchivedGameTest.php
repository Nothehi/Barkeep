<?php

use Modules\GameDesign\Application\Commands\ChangeDesignPhase;
use Modules\GameDesign\Application\Commands\CreateGameVersion;
use Modules\GameDesign\Application\Commands\UpdateGame;
use Modules\GameDesign\Application\DTOs\CreateGameVersionData;
use Modules\GameDesign\Application\DTOs\UpdateGameData;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * An archived game is read-only. This file is the proof.
 *
 * Read-only means every way in, not just the obvious one, so the checks below
 * come in pairs: the HTTP endpoint is refused, and the application command
 * behind it is refused too. The first is the policy doing its job; the second
 * is what stops a console command, a queued job or a later module from
 * writing to a game the product considers closed.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->archived()
        ->create();
});

it('still shows an archived game', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges')
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('refuses to rename an archived game', function () {
    $this->actingAs($this->designer)
        ->patchJson('/api/v1/workspaces/studio/games/bears-and-bridges', [
            'name' => 'Renamed',
            'slug' => 'renamed',
        ])
        ->assertForbidden();

    expect($this->game->fresh()?->name)->not->toBe('Renamed');
});

it('refuses to move an archived game through its lifecycle', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/status', ['status' => 'active'])
        ->assertForbidden();

    expect($this->game->fresh()?->status->value)->toBe('archived');
});

it('refuses to move an archived game\'s design phase', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/design-phase', [
            'design_phase' => 'published',
        ])
        ->assertForbidden();

    expect($this->game->fresh()?->design_phase->value)->toBe('idea');
});

it('refuses to record a version on an archived game', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions', [
            'description' => 'One more go.',
        ])
        ->assertForbidden();

    expect(GameVersion::query()->count())->toBe(0);
});

it('refuses to archive an already archived game', function () {
    $this->actingAs($this->designer)
        ->postJson('/api/v1/workspaces/studio/games/bears-and-bridges/archive')
        ->assertForbidden();
});

it('still shows an archived game\'s versions', function () {
    GameVersion::factory()->forGame($this->game)->numbered(1)->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/workspaces/studio/games/bears-and-bridges/versions')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

/**
 * The same rules, reached without HTTP. If these passed only at the boundary,
 * the read-only guarantee would be a property of the routes rather than of
 * the domain.
 */
it('refuses the commands themselves, not just the endpoints', function () {
    $update = fn () => app(UpdateGame::class)->handle(
        $this->designer,
        $this->game,
        new UpdateGameData(name: 'Renamed', slug: GameSlug::fromString('renamed')),
    );

    $phase = fn () => app(ChangeDesignPhase::class)->handle(
        $this->designer,
        $this->game,
        DesignPhase::Published,
    );

    $version = fn () => app(CreateGameVersion::class)->handle(
        $this->designer,
        $this->game,
        new CreateGameVersionData(description: 'One more go.'),
    );

    expect($update)->toThrow(GameIsNotModifiable::class)
        ->and($phase)->toThrow(GameIsNotModifiable::class)
        ->and($version)->toThrow(GameIsNotModifiable::class);

    expect($this->game->fresh()?->name)->not->toBe('Renamed')
        ->and(GameVersion::query()->count())->toBe(0);
});

/**
 * Restoration is not implemented. Archived is a terminal state, and the
 * transition matrix is where that fact lives.
 */
it('offers an archived game no way back', function () {
    expect($this->game->status->transitions())->toBe([])
        ->and($this->game->status->isTerminal())->toBeTrue();
});
