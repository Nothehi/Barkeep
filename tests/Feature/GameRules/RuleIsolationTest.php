<?php

use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The invariants the database cannot express, checked from the outside.
 *
 * Every one of these is the same shape: a record from somewhere else is named,
 * and the request has to fail. What differs is *how* it fails, and the difference
 * matters. An id that arrives as a route segment 404s, because the router could
 * not resolve it and admitting it exists would leak what another studio is
 * working on. An id that arrives in a request body 422s against its own field,
 * because the caller can see the form and needs to be told which picker was
 * wrong.
 */

beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();

    $this->game = Game::factory()
        ->inWorkspace($this->workspace)
        ->withSlug('bears-and-bridges')
        ->active()
        ->create();

    $this->version = GameVersion::factory()->nextFor($this->game)->create();
    $this->ruleSet = RuleSet::factory()->forVersion($this->version)->create();

    $this->otherRuleSet = RuleSet::factory()->forVersion($this->version)->create();
});

function isolationUrl(string $path, ?RuleSet $ruleSet = null): string
{
    $ruleSet ??= test()->ruleSet;
    $version = test()->version->version_number;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$ruleSet->id}".$path;
}

it('does not resolve a rule set belonging to another design version', function () {
    $otherVersion = GameVersion::factory()->nextFor($this->game)->create();
    $stranger = RuleSet::factory()->forVersion($otherVersion)->create();

    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$stranger->id}")
        ->assertNotFound();
});

it('does not resolve a rule set belonging to another workspace', function () {
    $outsider = User::factory()->create();
    $elsewhere = Workspace::factory()->ownedBy($outsider)->withSlug('elsewhere')->create();
    $otherGame = Game::factory()->inWorkspace($elsewhere)->withSlug('other-game')->active()->create();
    $otherVersion = GameVersion::factory()->nextFor($otherGame)->create();
    $stranger = RuleSet::factory()->forVersion($otherVersion)->create();

    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$stranger->id}")
        ->assertNotFound();
});

it('hides a rule set from somebody who cannot see the game', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->getJson(isolationUrl(''))
        ->assertNotFound();
});

it('does not resolve a rule from another rule set', function () {
    $stranger = GameRule::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->getJson(isolationUrl("/rules/{$stranger->id}"))
        ->assertNotFound();
});

it('does not resolve a phase from another rule set', function () {
    $stranger = GamePhase::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->patchJson(isolationUrl("/phases/{$stranger->id}"), ['name' => 'Renamed'])
        ->assertNotFound();
});

it('refuses a rule whose parent belongs to another rule set', function () {
    $stranger = GameRule::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/rules'), ['name' => 'Combat', 'parent_rule_id' => $stranger->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('parent_rule_id');
});

it('refuses a rule filed under a phase from another rule set', function () {
    $stranger = GamePhase::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/rules'), ['name' => 'Combat', 'phase_id' => $stranger->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('phase_id');
});

it('refuses a transition whose destination belongs to another rule set', function () {
    $mine = GamePhase::factory()->forRuleSet($this->ruleSet)->create();
    $stranger = GamePhase::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/transitions'), [
            'from_phase_id' => $mine->id,
            'to_phase_id' => $stranger->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('to_phase_id');
});

it('refuses a transition guarded by a condition from another rule set', function () {
    $from = GamePhase::factory()->forRuleSet($this->ruleSet)->create();
    $to = GamePhase::factory()->forRuleSet($this->ruleSet)->create();
    $stranger = RuleCondition::factory()->forRuleSet($this->otherRuleSet)->unary()->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/transitions'), [
            'from_phase_id' => $from->id,
            'to_phase_id' => $to->id,
            'condition_id' => $stranger->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('condition_id');
});

it('refuses a reference to a rule in another rule set', function () {
    $mine = GameRule::factory()->forRuleSet($this->ruleSet)->create();
    $stranger = GameRule::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl("/rules/{$mine->id}/references"), ['referenced_rule_id' => $stranger->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('referenced_rule_id');
});

it('refuses an effect owned by an action in another rule set', function () {
    $stranger = RuleAction::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/effects'), [
            'action_id' => $stranger->id,
            'target' => 'The board',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('action_id');
});

it('refuses an outcome measured by a condition from another rule set', function () {
    $stranger = RuleCondition::factory()->forRuleSet($this->otherRuleSet)->unary()->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/victory-conditions'), [
            'name' => 'First to twenty',
            'condition_id' => $stranger->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('condition_id');
});

it('refuses to put a condition from another rule set into a group', function () {
    $group = ConditionGroup::factory()->forRuleSet($this->ruleSet)->create();
    $stranger = RuleCondition::factory()->forRuleSet($this->otherRuleSet)->unary()->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl("/condition-groups/{$group->id}/conditions"), ['condition_id' => $stranger->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('condition_id');
});

it('refuses to reorder using an id from another rule set', function () {
    $mine = GameRule::factory()->forRuleSet($this->ruleSet)->create();
    $stranger = GameRule::factory()->forRuleSet($this->otherRuleSet)->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/rules/order'), ['rule_ids' => [$mine->id, $stranger->id]])
        ->assertStatus(422);

    expect($mine->fresh()->position)->toBe(0);
});

it('does not resolve a membership from another group', function () {
    $mine = ConditionGroup::factory()->forRuleSet($this->ruleSet)->create();
    $other = ConditionGroup::factory()->forRuleSet($this->ruleSet)->create();

    $condition = RuleCondition::factory()->forRuleSet($this->ruleSet)->unary()->create();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl("/condition-groups/{$other->id}/conditions"), ['condition_id' => $condition->id])
        ->assertOk();

    $membershipId = $other->memberships()->firstOrFail()->id;

    $this->actingAs($this->designer)
        ->deleteJson(isolationUrl("/condition-groups/{$mine->id}/conditions/{$membershipId}"))
        ->assertNotFound();
});

it('refuses everything to somebody who is not signed in', function () {
    $this->getJson(isolationUrl(''))->assertUnauthorized();
    $this->postJson(isolationUrl('/rules'), ['name' => 'Combat'])->assertUnauthorized();
});

it('refuses writes when the game has been archived', function () {
    $this->game->forceFill(['status' => GameStatus::Archived])->save();

    $this->actingAs($this->designer)
        ->postJson(isolationUrl('/rules'), ['name' => 'Combat'])
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->getJson(isolationUrl(''))
        ->assertOk();
});
