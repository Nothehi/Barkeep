<?php

use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * A game's relationship with a methodology, over time.
 *
 * Three statuses and one asymmetry worth keeping in view: pausing refuses new work and accepts being
 * resumed, so the check that guards the lifecycle is deliberately one status looser than the check
 * that guards progress. Without that difference, pausing would be a one-way door.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->framework = Framework::factory()->withSlug('bgdf')->published()->create();
    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->adoption = GameFramework::factory()
        ->forGame($this->game)
        ->following($this->version)
        ->adoptedBy($this->designer)
        ->create();

    $this->at = fn (string $action): string => "/app/workspaces/studio/games/bears-and-bridges/framework/{$action}";
});

it('steps away from the framework', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)('pause'))
        ->assertRedirect();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Paused);
});

it('picks the framework back up', function () {
    $this->adoption->status = GameFrameworkStatus::Paused;
    $this->adoption->save();

    $this->actingAs($this->designer)
        ->post(($this->at)('resume'))
        ->assertRedirect();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Active);
});

it('declares the game finished with its framework', function () {
    $this->actingAs($this->designer)
        ->post(($this->at)('complete'))
        ->assertRedirect();

    $adoption = $this->adoption->fresh();

    expect($adoption->status)->toBe(GameFrameworkStatus::Completed)
        ->and($adoption->completed_at)->not->toBeNull();
});

/**
 * Completing does not require the arithmetic to say a hundred per cent. Plenty of studios stop at
 * eighty because the last twenty was about a production run they are not doing, and refusing them
 * would be the module insisting it knows better than the person doing the work.
 */
it('lets a studio finish a framework it has barely started', function () {
    DesignCriterion::factory()->count(5)->inVersion($this->version)->create();

    $this->actingAs($this->designer)
        ->post(($this->at)('complete'))
        ->assertRedirect();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Completed);
});

it('completes a paused framework without resuming it first', function () {
    $this->adoption->status = GameFrameworkStatus::Paused;
    $this->adoption->save();

    $this->actingAs($this->designer)
        ->post(($this->at)('complete'))
        ->assertRedirect();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Completed);
});

/**
 * Completed is terminal. A studio that finishes a methodology and later wants back in is starting
 * again — which is adopting the version they mean, not a quiet reversal of a declaration they made.
 */
it('refuses every move once the framework is complete', function () {
    $this->adoption->status = GameFrameworkStatus::Completed;
    $this->adoption->save();

    foreach (['pause', 'resume', 'complete'] as $action) {
        $this->actingAs($this->designer)
            ->post(($this->at)($action))
            ->assertForbidden();
    }

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Completed);
});

/**
 * Reading survives everything the lifecycle does. A completed adoption keeps every evaluation it
 * gathered, because that record is the reason to have worked the framework at all.
 */
it('keeps the record a completed framework gathered', function () {
    $criterion = DesignCriterion::factory()->inVersion($this->version)->create();

    $this->actingAs($this->designer)
        ->post("/app/workspaces/studio/games/bears-and-bridges/framework/criteria/{$criterion->id}/evaluate", [
            'status' => 'good',
        ]);

    $this->actingAs($this->designer)->post(($this->at)('complete'));

    expect(CriterionEvaluation::query()->count())->toBe(1);

    $this->actingAs($this->designer)
        ->get('/app/workspaces/studio/games/bears-and-bridges/framework')
        ->assertOk();
});

it('refuses to move the lifecycle of an archived game', function () {
    $this->game->status = GameStatus::Archived;
    $this->game->save();

    $this->actingAs($this->designer)
        ->post(($this->at)('pause'))
        ->assertForbidden();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Active);
});

it('has nothing to move when the game follows no framework', function () {
    $other = Game::factory()->inWorkspace($this->workspace)->withSlug('untouched')->active()->create();

    $this->actingAs($this->designer)
        ->post('/app/workspaces/studio/games/untouched/framework/pause')
        ->assertNotFound();
});

it('hides the lifecycle from an outsider', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post(($this->at)('pause'))
        ->assertNotFound();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Active);
});

/**
 * A teammate is not a spectator. Every member of the workspace can work on every game in it, and
 * that includes deciding the studio has stepped away from its methodology.
 */
it('lets a teammate move the lifecycle', function () {
    $teammate = User::factory()->create();

    $this->workspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($teammate)
        ->post(($this->at)('pause'))
        ->assertRedirect();

    expect($this->adoption->fresh()->status)->toBe(GameFrameworkStatus::Paused);
});
