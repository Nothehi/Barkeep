<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Events\RuleSetCloned;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * Section 55 and 56 of the module brief. An active rule set cannot be edited, so
 * cloning is the only way forward — which means the copy has to be complete and
 * completely independent, or the second step becomes quietly destructive.
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
});

/**
 * A rule set with one of everything, and every relationship between them.
 */
function populatedRuleSet(): RuleSet
{
    $ruleSet = RuleSet::factory()->forVersion(test()->version)->named('Convention rules')->create();

    $round = GamePhase::factory()->forRuleSet($ruleSet)->named('Round')->setup()->atPosition(0)->create();
    $action = GamePhase::factory()->forRuleSet($ruleSet)->under($round)->named('Action phase')->atPosition(1)->create();
    $end = GamePhase::factory()->forRuleSet($ruleSet)->named('Game end')->terminal()->atPosition(2)->create();

    $condition = RuleCondition::factory()->forRuleSet($ruleSet)->named('All players have passed')->unary()->create();
    $trigger = RuleTrigger::factory()->forRuleSet($ruleSet)->named('At the end of the round')->create();

    PhaseTransition::factory()->between($round, $action)->create();
    PhaseTransition::factory()->between($action, $end)->guardedBy($condition)->triggeredBy($trigger)->create();

    RuleMechanic::factory()->forRuleSet($ruleSet)->named('Worker placement')->create();

    $combat = GameRule::factory()->forRuleSet($ruleSet)->named('Combat')->during($action)->create();
    $siege = GameRule::factory()->forRuleSet($ruleSet)->under($combat)->named('Siege')->create();

    RuleReference::factory()->from($siege, $combat)->ofType(ReferenceType::ExceptionTo)->create();

    $build = RuleAction::factory()->forRuleSet($ruleSet)->during($action)->named('Build')->pricedAs('build')->create();

    RuleRequirement::factory()->forAction($build)->costing('wood', '5')->create();
    RuleEffect::factory()->forAction($build)->moving('Victory points', '+3')->create();
    RuleEffect::factory()->forRule($combat)->create();

    $group = ConditionGroup::factory()->forRuleSet($ruleSet)->named('End of game check')->create();
    $membership = new ConditionGroupCondition;
    $membership->condition_group_id = $group->id;
    $membership->condition_id = $condition->id;
    $membership->position = 0;
    $membership->save();

    VictoryCondition::factory()->forRuleSet($ruleSet)->named('First to twenty')->measuredBy($condition)->create();
    DefeatCondition::factory()->forRuleSet($ruleSet)->named('Out of ships')->create();
    GameEndCondition::factory()->forRuleSet($ruleSet)->named('Deck runs out')->create();

    return $ruleSet;
}

function cloneUrl(RuleSet $ruleSet): string
{
    $version = test()->version->version_number;

    return "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$ruleSet->id}/clone";
}

it('copies every kind of record into the new draft', function () {
    Event::fake([RuleSetCloned::class]);

    $source = populatedRuleSet();

    $clone = RuleSet::query()->findOrFail(
        $this->actingAs($this->designer)->postJson(cloneUrl($source))->assertCreated()->json('data.id'),
    );

    expect($clone->status)->toBe(RuleSetStatus::Draft)
        ->and($clone->cloned_from_rule_set_id)->toBe($source->id)
        ->and($clone->game_version_id)->toBe($source->game_version_id)
        ->and($clone->phases()->count())->toBe(3)
        ->and($clone->conditions()->count())->toBe(1)
        ->and($clone->triggers()->count())->toBe(1)
        ->and($clone->transitions()->count())->toBe(2)
        ->and($clone->mechanics()->count())->toBe(1)
        ->and($clone->rules()->count())->toBe(2)
        ->and($clone->actions()->count())->toBe(1)
        ->and($clone->requirements()->count())->toBe(1)
        ->and($clone->effects()->count())->toBe(2)
        ->and($clone->conditionGroups()->count())->toBe(1)
        ->and($clone->victoryConditions()->count())->toBe(1)
        ->and($clone->defeatConditions()->count())->toBe(1)
        ->and($clone->endConditions()->count())->toBe(1);

    Event::assertDispatched(RuleSetCloned::class, fn (RuleSetCloned $event): bool => $event->sourceRuleSetId === $source->id && $event->recordsCopied > 0);
});

it('gives the copy a name the version does not already use', function () {
    $source = populatedRuleSet();

    $first = $this->actingAs($this->designer)->postJson(cloneUrl($source))->json('data.name');
    $second = $this->actingAs($this->designer)->postJson(cloneUrl($source))->json('data.name');

    expect($first)->not->toBe($source->name)
        ->and($second)->not->toBe($first)
        ->and($second)->not->toBe($source->name);
});

it('rewrites every pointer to the copied records', function () {
    $source = populatedRuleSet();

    $clone = RuleSet::query()->findOrFail(
        $this->actingAs($this->designer)->postJson(cloneUrl($source))->json('data.id'),
    );

    $clonedPhaseIds = $clone->phases()->pluck('id')->all();
    $clonedRuleIds = $clone->rules()->pluck('id')->all();
    $clonedConditionIds = $clone->conditions()->pluck('id')->all();

    /*
     * Nothing in the copy may point at a record in the original. This is the
     * assertion the whole operation exists for: it is what makes editing the
     * clone safe.
     */
    foreach ($clone->transitions as $transition) {
        expect($clonedPhaseIds)->toContain($transition->from_phase_id)
            ->and($clonedPhaseIds)->toContain($transition->to_phase_id);

        if ($transition->condition_id !== null) {
            expect($clonedConditionIds)->toContain($transition->condition_id);
        }
    }

    foreach ($clone->rules as $rule) {
        if ($rule->parent_rule_id !== null) {
            expect($clonedRuleIds)->toContain($rule->parent_rule_id);
        }

        if ($rule->phase_id !== null) {
            expect($clonedPhaseIds)->toContain($rule->phase_id);
        }
    }

    foreach ($clone->actions as $action) {
        expect($clonedPhaseIds)->toContain($action->phase_id);
    }

    foreach ($clone->victoryConditions as $outcome) {
        expect($clonedConditionIds)->toContain($outcome->condition_id);
    }
});

it('preserves the shape of the tree and the graph', function () {
    $source = populatedRuleSet();

    $clone = RuleSet::query()->findOrFail(
        $this->actingAs($this->designer)->postJson(cloneUrl($source))->json('data.id'),
    );

    $siege = $clone->rules()->where('slug', 'siege')->firstOrFail();
    $combat = $clone->rules()->where('slug', 'combat')->firstOrFail();

    expect($siege->parent_rule_id)->toBe($combat->id);

    $reference = $siege->references()->firstOrFail();

    expect($reference->referenced_rule_id)->toBe($combat->id)
        ->and($reference->reference_type)->toBe(ReferenceType::ExceptionTo);

    $actionPhase = $clone->phases()->where('slug', 'action_phase')->firstOrFail();
    $round = $clone->phases()->where('slug', 'round')->firstOrFail();

    expect($actionPhase->parent_phase_id)->toBe($round->id);
});

it('carries economy handles across without resolving them', function () {
    $source = populatedRuleSet();

    $clone = RuleSet::query()->findOrFail(
        $this->actingAs($this->designer)->postJson(cloneUrl($source))->json('data.id'),
    );

    expect($clone->actions()->firstOrFail()->economy_action_slug)->toBe('build')
        ->and($clone->requirements()->firstOrFail()->economy_resource_slug)->toBe('wood');
});

it('leaves the original completely untouched when the copy is changed', function () {
    $source = populatedRuleSet();

    $clone = RuleSet::query()->findOrFail(
        $this->actingAs($this->designer)->postJson(cloneUrl($source))->json('data.id'),
    );

    $version = $this->version->version_number;
    $base = "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$clone->id}";

    $clonedRule = $clone->rules()->where('slug', 'combat')->firstOrFail();

    $this->actingAs($this->designer)
        ->patchJson("{$base}/rules/{$clonedRule->id}", ['name' => 'Ranged combat'])
        ->assertOk();

    $this->actingAs($this->designer)
        ->deleteJson("{$base}/phases/".$clone->phases()->where('slug', 'game_end')->firstOrFail()->id)
        ->assertNoContent();

    expect($source->rules()->where('slug', 'combat')->firstOrFail()->name)->toBe('Combat')
        ->and($source->phases()->count())->toBe(3)
        ->and($source->transitions()->count())->toBe(2);
});

it('copies a rule set that is in play, which is the whole point', function () {
    $source = populatedRuleSet();

    $version = $this->version->version_number;
    $base = "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$source->id}";

    $this->actingAs($this->designer)->postJson("{$base}/activate")->assertOk();

    $this->actingAs($this->designer)
        ->postJson(cloneUrl($source))
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft');
});

it('copies an archived rule set too', function () {
    $source = populatedRuleSet();

    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->postJson("/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$source->id}/archive")
        ->assertOk();

    $this->actingAs($this->designer)->postJson(cloneUrl($source))->assertCreated();
});
