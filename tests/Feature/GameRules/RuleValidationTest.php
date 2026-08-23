<?php

use Illuminate\Support\Facades\Event;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\ValidationCode;
use Modules\GameRules\Domain\Enums\ValidationSeverity;
use Modules\GameRules\Domain\Events\RuleSetAnalysed;
use Modules\GameRules\Domain\Events\RuleSetValidated;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Analysis\RuleGraphBuilder;
use Modules\GameRules\Infrastructure\Analysis\RuleSetValidator;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The validator reports and never refuses. Every one of these findings is saved
 * without complaint — a rule set is written over weeks, and for most of that time
 * it is full of them.
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
});

/**
 * @return list<string>
 */
function codes(?RuleSet $ruleSet = null): array
{
    return array_map(
        fn (ValidationError $finding): string => $finding->code->value,
        app(RuleSetValidator::class)->validate($ruleSet ?? test()->ruleSet),
    );
}

it('notices an empty rule set in every way at once', function () {
    expect(codes())
        ->toContain(ValidationCode::RuleSetHasNoRules->value)
        ->toContain(ValidationCode::RuleSetHasNoPhases->value)
        ->toContain(ValidationCode::RuleSetHasNoActions->value)
        ->toContain(ValidationCode::RuleSetHasNoMechanics->value)
        ->toContain(ValidationCode::RuleSetHasNoVictoryCondition->value)
        ->toContain(ValidationCode::RuleSetHasNoEndCondition->value);
});

it('notices an action nobody can place in the turn', function () {
    RuleAction::factory()->forRuleSet($this->ruleSet)->create();

    expect(codes())->toContain(ValidationCode::ActionHasNoPhase->value);
});

it('treats an action with no phase as an error rather than a warning', function () {
    RuleAction::factory()->forRuleSet($this->ruleSet)->create();

    $errors = array_filter(
        app(RuleSetValidator::class)->validate($this->ruleSet),
        fn (ValidationError $finding): bool => $finding->code === ValidationCode::ActionHasNoPhase,
    );

    expect($errors)->toHaveCount(1)
        ->and(reset($errors)->severity())->toBe(ValidationSeverity::Error);
});

it('notices a phase play cannot leave', function () {
    GamePhase::factory()->forRuleSet($this->ruleSet)->named('Action phase')->create();

    expect(codes())->toContain(ValidationCode::PhaseHasNoOutgoingTransition->value);
});

it('does not ask a terminal phase for an exit', function () {
    GamePhase::factory()->forRuleSet($this->ruleSet)->named('Game end')->terminal()->create();

    expect(codes())->not->toContain(ValidationCode::PhaseHasNoOutgoingTransition->value);
});

it('notices a phase play never arrives at', function () {
    $setup = GamePhase::factory()->forRuleSet($this->ruleSet)->setup()->named('Setup')->atPosition(0)->create();
    $end = GamePhase::factory()->forRuleSet($this->ruleSet)->terminal()->named('Game end')->atPosition(1)->create();
    GamePhase::factory()->forRuleSet($this->ruleSet)->terminal()->named('Orphan')->atPosition(2)->create();

    PhaseTransition::factory()->between($setup, $end)->create();

    expect(codes())->toContain(ValidationCode::PhaseIsUnreachable->value);
});

it('skips a deprecated phase', function () {
    GamePhase::factory()
        ->forRuleSet($this->ruleSet)
        ->named('Old scoring phase')
        ->state(['status' => RuleStatus::Deprecated])
        ->create();

    expect(codes())->not->toContain(ValidationCode::PhaseHasNoOutgoingTransition->value);
});

it('notices a comparison against text', function () {
    RuleCondition::factory()
        ->forRuleSet($this->ruleSet)
        ->named('Rounds elapsed')
        ->comparing(ConditionType::Counter, ConditionOperator::GreaterThan, 'blue')
        ->create();

    expect(codes())->toContain(ValidationCode::ConditionValueIsNotNumeric->value);
});

it('notices a condition nothing points at', function () {
    RuleCondition::factory()->forRuleSet($this->ruleSet)->unary()->create();

    expect(codes())->toContain(ValidationCode::ConditionIsUnused->value);
});

it('does not report a condition a victory condition measures', function () {
    $condition = RuleCondition::factory()->forRuleSet($this->ruleSet)->unary()->create();

    VictoryCondition::factory()->forRuleSet($this->ruleSet)->measuredBy($condition)->create();

    expect(codes())->not->toContain(ValidationCode::ConditionIsUnused->value);
});

it('notices an empty condition group', function () {
    ConditionGroup::factory()->forRuleSet($this->ruleSet)->create();

    expect(codes())->toContain(ValidationCode::ConditionGroupIsEmpty->value);
});

it('notices a trigger nothing points at', function () {
    RuleTrigger::factory()->forRuleSet($this->ruleSet)->create();

    expect(codes())->toContain(ValidationCode::TriggerIsUnused->value);
});

it('notices an effect that does not say how much', function () {
    $rule = GameRule::factory()->forRuleSet($this->ruleSet)->create();

    RuleEffect::factory()->forRule($rule)->ofType(EffectType::Resource)->create();

    expect(codes())->toContain(ValidationCode::EffectHasNoValue->value);
});

it('notices an outcome nobody can measure', function () {
    VictoryCondition::factory()->forRuleSet($this->ruleSet)->create();

    expect(codes())->toContain(ValidationCode::VictoryConditionHasNoCondition->value);
});

it('notices a rule that is only a heading', function () {
    GameRule::factory()->forRuleSet($this->ruleSet)->state(['description' => null])->create();

    expect(codes())->toContain(ValidationCode::RuleHasNoDescription->value);
});

it('exempts a rule with children from needing wording of its own', function () {
    $parent = GameRule::factory()->forRuleSet($this->ruleSet)->named('Combat')->state(['description' => null])->create();
    GameRule::factory()->forRuleSet($this->ruleSet)->under($parent)->named('Apply damage')->create();

    $headings = array_filter(
        app(RuleSetValidator::class)->validate($this->ruleSet),
        fn (ValidationError $finding): bool => $finding->code === ValidationCode::RuleHasNoDescription
            && $finding->entityId === $parent->id,
    );

    expect($headings)->toBeEmpty();
});

it('lists the errors before the warnings', function () {
    RuleAction::factory()->forRuleSet($this->ruleSet)->create();

    $findings = app(RuleSetValidator::class)->validate($this->ruleSet);

    $severities = array_map(
        fn (ValidationError $finding): int => $finding->severity()->weight(),
        $findings,
    );

    $sorted = $severities;
    sort($sorted);

    expect($severities)->toBe($sorted);
});

it('says nothing about economy handles when the version has no economy', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->create();

    RuleAction::factory()->forRuleSet($this->ruleSet)->during($phase)->pricedAs('build')->create();

    expect(codes())->not->toContain(ValidationCode::EconomyReferenceIsUnresolved->value);
});

it('draws the flow of a game from its phases and transitions', function () {
    $setup = GamePhase::factory()->forRuleSet($this->ruleSet)->setup()->named('Setup')->atPosition(0)->create();
    $action = GamePhase::factory()->forRuleSet($this->ruleSet)->named('Action phase')->atPosition(1)->create();
    $end = GamePhase::factory()->forRuleSet($this->ruleSet)->terminal()->named('Game end')->atPosition(2)->create();

    PhaseTransition::factory()->between($setup, $action)->create();
    PhaseTransition::factory()->between($action, $end)->create();

    RuleAction::factory()->forRuleSet($this->ruleSet)->during($action)->named('Build')->create();

    $graph = app(RuleGraphBuilder::class)->build($this->ruleSet);

    expect($graph->nodes)->toHaveCount(4)
        ->and($graph->edges)->toHaveCount(3)
        ->and($graph->unreachable)->toBe([])
        ->and($graph->nodes[0]->key)->toBe('start')
        ->and($graph->edges[0]->isImplicit)->toBeTrue();

    $actionNode = collect($graph->nodes)->firstOrFail(fn ($node): bool => $node->label === 'Action phase');

    expect($actionNode->actions)->toBe(['Build']);
});

it('labels an arrow with the sentence that guards it', function () {
    $from = GamePhase::factory()->forRuleSet($this->ruleSet)->setup()->atPosition(0)->create();
    $to = GamePhase::factory()->forRuleSet($this->ruleSet)->terminal()->atPosition(1)->create();

    $condition = RuleCondition::factory()
        ->forRuleSet($this->ruleSet)
        ->named('Score')
        ->comparing(ConditionType::Score, ConditionOperator::GreaterThanOrEqual, '20')
        ->create();

    PhaseTransition::factory()->between($from, $to)->guardedBy($condition)->create();

    $graph = app(RuleGraphBuilder::class)->build($this->ruleSet);

    $guarded = collect($graph->edges)->firstOrFail(fn ($edge): bool => $edge->label !== null);

    expect($guarded->label)->toBe('Score is at least 20');
});

it('returns an empty graph for a rule set with no phases', function () {
    $graph = app(RuleGraphBuilder::class)->build($this->ruleSet);

    expect($graph->isEmpty())->toBeTrue()
        ->and($graph->nodes)->toHaveCount(1);
});

it('counts everything and folds the findings into the summary', function () {
    $phase = GamePhase::factory()->forRuleSet($this->ruleSet)->create();
    GameRule::factory()->count(3)->forRuleSet($this->ruleSet)->create();
    RuleAction::factory()->forRuleSet($this->ruleSet)->during($phase)->create();

    $version = $this->version->version_number;

    $this->actingAs($this->designer)
        ->getJson("/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$this->ruleSet->id}/analysis")
        ->assertOk()
        ->assertJsonPath('data.summary.rules', 3)
        ->assertJsonPath('data.summary.phases', 1)
        ->assertJsonPath('data.summary.actions', 1)
        ->assertJsonPath('data.summary.is_empty', false);
});

it('announces a deliberate check but stays silent on a read', function () {
    Event::fake([
        RuleSetValidated::class,
        RuleSetAnalysed::class,
    ]);

    $version = $this->version->version_number;
    $base = "/api/v1/workspaces/studio/games/bears-and-bridges/versions/{$version}/rule-sets/{$this->ruleSet->id}";

    $this->actingAs($this->designer)->getJson("{$base}/analysis")->assertOk();

    Event::assertNotDispatched(RuleSetAnalysed::class);

    $this->actingAs($this->designer)->postJson("{$base}/analysis")->assertOk();
    $this->actingAs($this->designer)->postJson("{$base}/validate")->assertOk();

    Event::assertDispatched(RuleSetAnalysed::class);
    Event::assertDispatched(RuleSetValidated::class);
});
