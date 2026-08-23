<?php

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;
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
    $this->ruleSet = RuleSet::factory()->forVersion($this->version)->create();
});

function setUrl(string $path = ''): string
{
    $ruleSet = test()->ruleSet;
    $version = test()->version->version_number;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$ruleSet->id}".$path;
}

function post(string $path, array $payload = [])
{
    return test()->actingAs(test()->designer)->postJson(setUrl($path), $payload);
}

it('writes a rule and derives its handle from the name', function () {
    post('/rules', ['name' => 'Line of sight', 'description' => 'You may only attack what you can see.'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'line_of_sight')
        ->assertJsonPath('data.rule_type', 'general');
});

it('refuses two rules whose names produce the same handle', function () {
    post('/rules', ['name' => 'Combat'])->assertCreated();

    post('/rules', ['name' => 'combat'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('nests a rule under another and counts its children', function () {
    $parent = GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->create();

    post('/rules', ['name' => 'Declare attacks', 'parent_rule_id' => $parent->id])->assertCreated();
    post('/rules', ['name' => 'Apply damage', 'parent_rule_id' => $parent->id])->assertCreated();

    $this->actingAs($this->designer)
        ->getJson(setUrl("/rules/{$parent->id}"))
        ->assertOk()
        ->assertJsonCount(2, 'data.children');
});

it('promotes children rather than deleting them when a heading goes', function () {
    $parent = GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->create();
    $child = GameRule::factory()->forRuleSet($this->ruleSet)->under($parent)->named('Apply damage')->create();

    $this->actingAs($this->designer)
        ->deleteJson(setUrl("/rules/{$parent->id}"))
        ->assertNoContent();

    expect($child->fresh())->not->toBeNull()
        ->and($child->fresh()->parent_rule_id)->toBeNull();
});

it('reorders the rules from the list the client sends', function () {
    $first = GameRule::factory()->forRuleSet($this->ruleSet)->named('A')->atPosition(0)->create();
    $second = GameRule::factory()->forRuleSet($this->ruleSet)->named('B')->atPosition(1)->create();
    $third = GameRule::factory()->forRuleSet($this->ruleSet)->named('C')->atPosition(2)->create();

    post('/rules/order', ['rule_ids' => [$third->id, $first->id, $second->id]])->assertOk();

    expect($third->fresh()->position)->toBe(0)
        ->and($first->fresh()->position)->toBe(1)
        ->and($second->fresh()->position)->toBe(2);
});

it('files a rule under a part of the game', function () {
    post('/rules', ['name' => 'Setting up', 'rule_type' => RuleType::Setup->value])
        ->assertCreated()
        ->assertJsonPath('data.rule_type', 'setup');
});

it('names a mechanism the rule system uses', function () {
    post('/mechanics', ['name' => 'Worker placement', 'category' => 'action'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'worker_placement')
        ->assertJsonPath('data.category', 'action');
});

it('declares an action and places it in a phase', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->named('Action phase')->create();

    post('/actions', ['name' => 'Build', 'phase_id' => $phase->id])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'build')
        ->assertJsonPath('data.phase_id', $phase->id);
});

it('draws a transition between two phases and labels it with its condition', function () {
    $from = GamePhase::factory()->forRuleSet($this->ruleSet)->named('Action phase')->create();
    $to = GamePhase::factory()->forRuleSet($this->ruleSet)->named('Resolution')->create();

    $condition = RuleCondition::factory()
        ->forRuleSet($this->ruleSet)
        ->named('All players have passed')
        ->unary()
        ->create();

    post('/transitions', [
        'from_phase_id' => $from->id,
        'to_phase_id' => $to->id,
        'condition_id' => $condition->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.from_phase_name', 'Action phase')
        ->assertJsonPath('data.is_guarded', true)
        ->assertJsonPath('data.condition_statement', 'All players have passed is true');
});

it('refuses a transition that leads back to where it started', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->create();

    post('/transitions', ['from_phase_id' => $phase->id, 'to_phase_id' => $phase->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('to_phase_id');
});

it('gates an action on a requirement and records what it does', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->create();
    $action = RuleAction::factory()->forRuleSet($this->ruleSet)->during($phase)->named('Build')->create();

    post('/requirements', [
        'action_id' => $action->id,
        'requirement_type' => 'resource',
        'description' => 'You hold at least five wood.',
        'value' => '5',
        'economy_resource_slug' => 'wood',
    ])->assertCreated();

    post('/effects', [
        'action_id' => $action->id,
        'effect_type' => EffectType::Score->value,
        'target' => 'Victory points',
        'value' => '+3',
    ])->assertCreated();

    $this->actingAs($this->designer)
        ->getJson(setUrl("/actions/{$action->id}"))
        ->assertOk()
        ->assertJsonCount(1, 'data.requirements')
        ->assertJsonCount(1, 'data.effects')
        ->assertJsonPath('data.effects.0.value', '+3');
});

it('refuses an effect that belongs to both a rule and an action', function () {
    $rule = GameRule::factory()->forRuleSet($this->ruleSet)->create();
    $action = RuleAction::factory()->forRuleSet($this->ruleSet)->create();

    post('/effects', [
        'rule_id' => $rule->id,
        'action_id' => $action->id,
        'target' => 'The board',
    ])->assertStatus(422);
});

it('refuses an effect that belongs to nothing', function () {
    post('/effects', ['target' => 'The board'])->assertStatus(422);
});

it('combines conditions into a group and takes one out again', function () {
    $first = RuleCondition::factory()->forRuleSet($this->ruleSet)->named('Deck is empty')->unary()->create();
    $second = RuleCondition::factory()->forRuleSet($this->ruleSet)->named('Round eight is over')->unary()->create();

    $group = post('/condition-groups', ['name' => 'End of game check', 'logic_operator' => 'or'])
        ->assertCreated()
        ->json('data.id');

    post("/condition-groups/{$group}/conditions", ['condition_id' => $first->id])->assertOk();
    post("/condition-groups/{$group}/conditions", ['condition_id' => $second->id])->assertOk();

    $listed = $this->actingAs($this->designer)->getJson(setUrl('/condition-groups'))->assertOk();

    expect($listed->json('data.0.conditions'))->toHaveCount(2)
        ->and($listed->json('data.0.logic_operator'))->toBe('or');

    $membershipId = $listed->json('data.0.memberships.0.id');

    $this->actingAs($this->designer)
        ->deleteJson(setUrl("/condition-groups/{$group}/conditions/{$membershipId}"))
        ->assertNoContent();

    expect($this->actingAs($this->designer)->getJson(setUrl('/condition-groups'))->json('data.0.conditions'))
        ->toHaveCount(1);
});

it('ignores a condition added to a group twice', function () {
    $condition = RuleCondition::factory()->forRuleSet($this->ruleSet)->unary()->create();

    $group = post('/condition-groups', ['name' => 'Check'])->json('data.id');

    post("/condition-groups/{$group}/conditions", ['condition_id' => $condition->id])->assertOk();
    post("/condition-groups/{$group}/conditions", ['condition_id' => $condition->id])->assertOk();

    expect($this->actingAs($this->designer)->getJson(setUrl('/condition-groups'))->json('data.0.conditions'))
        ->toHaveCount(1);
});

it('records the three kinds of outcome separately', function () {
    $condition = RuleCondition::factory()
        ->forRuleSet($this->ruleSet)
        ->named('Score reaches twenty')
        ->comparing(
            ConditionType::Score,
            ConditionOperator::GreaterThanOrEqual,
            '20',
        )
        ->create();

    post('/victory-conditions', ['name' => 'First to twenty', 'condition_id' => $condition->id])
        ->assertCreated()
        ->assertJsonPath('data.is_measurable', true);

    post('/defeat-conditions', ['name' => 'Out of ships'])
        ->assertCreated()
        ->assertJsonPath('data.is_measurable', false);

    post('/end-conditions', ['name' => 'The deck runs out'])->assertCreated();

    expect($this->ruleSet->victoryConditions()->count())->toBe(1)
        ->and($this->ruleSet->defeatConditions()->count())->toBe(1)
        ->and($this->ruleSet->endConditions()->count())->toBe(1);
});

it('keeps an outcome when the condition that measured it is deleted', function () {
    $condition = RuleCondition::factory()->forRuleSet($this->ruleSet)->unary()->create();

    $outcome = post('/victory-conditions', ['name' => 'First to twenty', 'condition_id' => $condition->id])
        ->json('data.id');

    $this->actingAs($this->designer)
        ->deleteJson(setUrl("/conditions/{$condition->id}"))
        ->assertNoContent();

    $listed = $this->actingAs($this->designer)->getJson(setUrl('/victory-conditions'))->assertOk();

    expect($listed->json('data.0.id'))->toBe($outcome)
        ->and($listed->json('data.0.is_measurable'))->toBeFalse();
});

it('relates one rule to another', function () {
    $combat = GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->create();
    $siege = GameRule::factory()->forRuleSet($this->ruleSet)->named('Siege')->create();

    post("/rules/{$siege->id}/references", [
        'referenced_rule_id' => $combat->id,
        'reference_type' => ReferenceType::ExceptionTo->value,
    ])
        ->assertCreated()
        ->assertJsonPath('data.referenced_rule_name', 'Combat')
        ->assertJsonPath('data.is_directed', true);
});

it('clears an effect amount that is sent as null', function () {
    $rule = GameRule::factory()->forRuleSet($this->ruleSet)->create();

    $effect = RuleEffect::factory()->forRule($rule)->moving('Wood', '5')->create();

    $this->actingAs($this->designer)
        ->patchJson(setUrl("/effects/{$effect->id}"), ['value' => null])
        ->assertOk()
        ->assertJsonPath('data.value', null);
});

it('leaves a field alone when the request does not mention it', function () {
    $rule = GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->create();

    $this->actingAs($this->designer)
        ->patchJson(setUrl("/rules/{$rule->id}"), ['name' => 'Melee combat'])
        ->assertOk();

    expect($rule->fresh()->description)->toBe($rule->description);
});
