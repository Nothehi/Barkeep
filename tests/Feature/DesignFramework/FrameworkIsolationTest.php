<?php

use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Two studios following the same published edition, and neither able to see anything of the other's
 * work.
 *
 * This is the module's central claim tested end to end. The methodology is shared — one framework,
 * one edition, the same criteria asked of both games — and everything recorded against it hangs off
 * each game's own adoption. If that separation were wrong anywhere, it would be visible here.
 *
 * The chain being tested is the security model:
 *
 *     account → workspace membership → game access → adoption → content
 *
 * Every segment is resolved through the one before it, so a caller who holds a valid uuid still
 * fails at the point where that record does not belong to what the rest of the address says it does.
 */
beforeEach(function () {
    $this->ours = User::factory()->create();
    $this->theirs = User::factory()->create();

    $this->ourWorkspace = Workspace::factory()->ownedBy($this->ours)->withSlug('studio')->create();
    $this->theirWorkspace = Workspace::factory()->ownedBy($this->theirs)->withSlug('rivals')->create();

    $this->ourGame = Game::factory()->inWorkspace($this->ourWorkspace)->withSlug('ours')->active()->create();
    $this->theirGame = Game::factory()->inWorkspace($this->theirWorkspace)->withSlug('theirs')->active()->create();

    $this->framework = Framework::factory()->withSlug('bgdf')->published()->create();
    $this->version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->criterion = DesignCriterion::factory()
        ->inVersion($this->version)
        ->titled('Does the core loop work?')
        ->create();

    $this->ourAdoption = GameFramework::factory()
        ->forGame($this->ourGame)
        ->following($this->version)
        ->adoptedBy($this->ours)
        ->create();

    $this->theirAdoption = GameFramework::factory()
        ->forGame($this->theirGame)
        ->following($this->version)
        ->adoptedBy($this->theirs)
        ->create();
});

/**
 * The whole point. One criterion, two games, two independent answers — and neither studio can see
 * or affect the other's.
 */
it('keeps two studios\' answers to the same question apart', function () {
    $this->actingAs($this->ours)
        ->post("/app/workspaces/studio/games/ours/framework/criteria/{$this->criterion->id}/evaluate", [
            'status' => 'weak',
        ])
        ->assertRedirect();

    $this->actingAs($this->theirs)
        ->post("/app/workspaces/rivals/games/theirs/framework/criteria/{$this->criterion->id}/evaluate", [
            'status' => 'strong',
        ])
        ->assertRedirect();

    expect(CriterionEvaluation::query()->count())->toBe(2);

    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/ours/framework/evaluations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'weak');

    $this->actingAs($this->theirs)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/framework/evaluations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'strong');
});

it('hides another studio\'s framework entirely', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/framework')
        ->assertNotFound();

    $this->actingAs($this->ours)
        ->get(route('games.framework.show', ['rivals', 'theirs']))
        ->assertNotFound();
});

it('hides another studio\'s progress', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/rivals/games/theirs/framework/progress')
        ->assertNotFound();
});

/**
 * A game named under the wrong workspace does not resolve at all, which is GameDesign's binding
 * doing its job before anything in this module runs.
 */
it('refuses a game addressed through the wrong workspace', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/theirs/framework')
        ->assertNotFound();
});

it('refuses to record work against another studio\'s game', function () {
    $this->actingAs($this->ours)
        ->post("/app/workspaces/rivals/games/theirs/framework/criteria/{$this->criterion->id}/evaluate", [
            'status' => 'good',
        ])
        ->assertNotFound();

    expect(CriterionEvaluation::query()->count())->toBe(0);
});

it('refuses to move another studio\'s adoption', function () {
    $this->actingAs($this->ours)
        ->post('/app/workspaces/rivals/games/theirs/framework/pause')
        ->assertNotFound();

    expect($this->theirAdoption->fresh()->status->value)->toBe('active');
});

/**
 * Framework content ids are not secrets — a criterion belongs to a globally published edition, and
 * both studios legitimately see the same one. What they cannot do is reach each other's record
 * through it.
 */
it('lets both studios address the same criterion', function () {
    foreach ([[$this->ours, 'studio', 'ours'], [$this->theirs, 'rivals', 'theirs']] as [$user, $workspace, $game]) {
        $this->actingAs($user)
            ->post("/app/workspaces/{$workspace}/games/{$game}/framework/criteria/{$this->criterion->id}/evaluate", [
                'status' => 'good',
            ])
            ->assertRedirect();
    }

    expect(CriterionEvaluation::query()->count())->toBe(2);
});

/**
 * A shared studio is shared. Every member of a workspace can work on every game in it, framework
 * included — the module adds no second, narrower notion of who may.
 */
it('lets a teammate read and record against the studio\'s framework', function () {
    $teammate = User::factory()->create();

    $this->ourWorkspace->members()->create([
        'user_id' => $teammate->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($teammate)
        ->getJson('/api/v1/workspaces/studio/games/ours/framework')
        ->assertOk();

    $this->actingAs($teammate)
        ->post("/app/workspaces/studio/games/ours/framework/criteria/{$this->criterion->id}/evaluate", [
            'status' => 'good',
        ])
        ->assertRedirect();

    expect(CriterionEvaluation::query()->sole()->evaluated_by)->toBe($teammate->id);
});

it('turns away a caller with no session at all', function () {
    $this->getJson('/api/v1/workspaces/studio/games/ours/framework')
        ->assertUnauthorized();
});

it('does not choke on a game slug that names nothing', function () {
    $this->actingAs($this->ours)
        ->getJson('/api/v1/workspaces/studio/games/no-such-game/framework')
        ->assertNotFound();
});
