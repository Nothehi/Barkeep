<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Two studios, each with a game, a design state and an economy, and neither able to see anything of
 * the other's.
 *
 * The chain being tested is the whole security model of the module:
 *
 *     account → workspace membership → game access → design version → profile → configuration
 *
 * Every segment of every URL is resolved through the one before it, so a caller who guesses a valid
 * uuid still fails at the point where that record does not belong to what the rest of the address
 * says it does. Where a record is hidden rather than refused, the response is a 404 — a profile id
 * must not confirm that a game exists to somebody who was not allowed to know.
 */
beforeEach(function () {
    $this->ours = User::factory()->create();
    $this->theirs = User::factory()->create();

    $this->ourWorkspace = Workspace::factory()->ownedBy($this->ours)->withSlug('studio')->create();
    $this->theirWorkspace = Workspace::factory()->ownedBy($this->theirs)->withSlug('rivals')->create();

    $this->ourGame = Game::factory()->inWorkspace($this->ourWorkspace)->withSlug('ours')->active()->create();
    $this->theirGame = Game::factory()->inWorkspace($this->theirWorkspace)->withSlug('theirs')->active()->create();

    $this->ourVersion = GameVersion::factory()->nextFor($this->ourGame)->create();
    $this->theirVersion = GameVersion::factory()->nextFor($this->theirGame)->create();

    $this->ourProfile = BalanceProfile::factory()->forVersion($this->ourVersion)->named('Ours')->create();
    $this->theirProfile = BalanceProfile::factory()->forVersion($this->theirVersion)->named('Theirs')->create();

    $this->ourWood = ResourceType::factory()->forProfile($this->ourProfile)->named('Wood')->create();
    $this->theirWood = ResourceType::factory()->forProfile($this->theirProfile)->named('Wood')->create();

    $this->ourUrl = "/api/v1/workspaces/studio/games/ours/versions/{$this->ourVersion->version_number}/balance-profiles";
});

it('shows a studio only its own configurations', function () {
    $this->actingAs($this->ours)
        ->getJson($this->ourUrl)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Ours');
});

it('hides another studio\'s configuration behind a 404 rather than a refusal', function () {
    $this->actingAs($this->theirs)
        ->getJson("{$this->ourUrl}/{$this->ourProfile->id}")
        ->assertNotFound();
});

it('does not reach a configuration through the wrong game', function () {
    $this->actingAs($this->theirs)
        ->getJson(
            "/api/v1/workspaces/rivals/games/theirs/versions/{$this->theirVersion->version_number}"
            ."/balance-profiles/{$this->ourProfile->id}",
        )
        ->assertNotFound();
});

it('does not reach a resource through another studio\'s configuration', function () {
    $this->actingAs($this->ours)
        ->patchJson("{$this->ourUrl}/{$this->ourProfile->id}/resources/{$this->theirWood->id}", ['name' => 'Taken'])
        ->assertNotFound();

    expect($this->theirWood->fresh()->name)->toBe('Wood');
});

it('does not reach an action line through another studio\'s action', function () {
    $theirAction = EconomyAction::factory()->forProfile($this->theirProfile)->named('Build')->create();

    $this->actingAs($this->ours)
        ->getJson("{$this->ourUrl}/{$this->ourProfile->id}/actions/{$theirAction->id}/costs")
        ->assertNotFound();
});

it('does not reach a variable through another studio\'s configuration', function () {
    $theirs = BalanceVariable::factory()->forProfile($this->theirProfile)->named('Starting gold')->create();

    $this->actingAs($this->ours)
        ->patchJson("{$this->ourUrl}/{$this->ourProfile->id}/variables/{$theirs->id}", ['value' => '99'])
        ->assertNotFound();

    expect($theirs->fresh()->value->label())->toBe('10');
});

/**
 * Membership is what grants access, not ownership. A member of the studio reads and tunes its
 * economy exactly as the owner does — balancing is the work, and gating it on administration would
 * be a strange shape for a design tool.
 */
it('lets any member of the studio read and tune the economy', function () {
    $member = User::factory()->create();

    $this->ourWorkspace->members()->create([
        'user_id' => $member->id,
        'role' => WorkspaceRole::Member,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->getJson($this->ourUrl)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($member)
        ->postJson("{$this->ourUrl}/{$this->ourProfile->id}/resources", ['name' => 'Clay'])
        ->assertCreated();
});

it('refuses an account with no membership at all', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->getJson($this->ourUrl)
        ->assertNotFound();
});

/**
 * The point of a per-version economy, stated as a test.
 *
 * Wood income was 2 in v1 and 3 in v2. Both profiles exist, both stay readable, and nothing about
 * tuning the second reaches back into the first — which is what makes a playtest run against v1
 * still interpretable a year later.
 */
it('keeps one design state\'s numbers out of another\'s', function () {
    $next = GameVersion::factory()->nextFor($this->ourGame)->create();
    $laterProfile = BalanceProfile::factory()->forVersion($next)->named('Ours')->create();

    ResourceType::factory()->forProfile($laterProfile)->named('Wood')->bounded(null, '40')->create();

    $this->actingAs($this->ours)
        ->patchJson(
            "/api/v1/workspaces/studio/games/ours/versions/{$next->version_number}"
            ."/balance-profiles/{$laterProfile->id}/resources/{$this->ourWood->id}",
            ['max_value' => '99'],
        )
        ->assertNotFound();

    expect($this->ourWood->fresh()->max_value)->toBeNull();
});
