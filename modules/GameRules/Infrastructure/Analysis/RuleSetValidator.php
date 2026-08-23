<?php

namespace Modules\GameRules\Infrastructure\Analysis;

use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleEntityType;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Enums\ValidationCode;
use Modules\GameRules\Domain\Models\ConditionGroup;
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
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\GameEconomy\EconomyDirectory;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Everything the module knows how to notice about a rule system.
 *
 * Static, deterministic and read-only. It does not execute the game, simulate a
 * turn or evaluate a condition — section 31 of the brief is explicit that this is
 * analysis rather than play, and section 33 is explicit that nothing in this
 * module executes anything at all.
 *
 * ## Reporting, not refusing
 *
 * Not one finding here changes a record and not one blocks a save. A rule set is
 * written over weeks: for most of that time it has no victory condition, half its
 * phases have no exit and several actions have no effects. A validator that
 * refused any of that would be a validator nobody could start with.
 *
 * The single exception is activation, and it is enforced by the command rather
 * than here: `ActivateRuleSet` refuses while any *error* stands, because "these
 * are the rules now" is a claim a rule that is its own ancestor makes false.
 * Warnings never block — a game with no way to win may be exactly what the
 * designer meant.
 *
 * ## How it is put together
 *
 * Each check is its own method, named for what it looks for, so adding one is
 * adding a method and a case to {@see ValidationCode} rather than editing a long
 * conditional. The severity comes from the code, so "an action with no phase is
 * an error" has exactly one definition.
 *
 * The whole run is a fixed number of queries regardless of how large the rule set
 * is: eleven collections are loaded once at the top and every check reads from
 * those. Reachability is not recomputed either — it is asked of
 * {@see RuleGraphBuilder}, so the validator and the diagram can never disagree
 * about which phases play arrives at.
 *
 * Deprecated rules, phases and actions are skipped throughout. A rule kept for
 * the record is not a rule that has to work.
 */
final class RuleSetValidator
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly CycleDetector $cycles,
        private readonly RuleGraphBuilder $graphs,
        private readonly EconomyDirectory $economy,
    ) {}

    /**
     * Every finding in a rule set, worst first.
     *
     * The ordering is by severity and then by the order the checks run, which
     * puts the errors at the top of the screen where somebody triaging will read
     * them — and keeps the rest in a stable order so a list does not reshuffle
     * between two identical runs.
     *
     * @return list<ValidationError>
     */
    public function validate(RuleSet $ruleSet): array
    {
        $rules = $this->structure->rulesOf($ruleSet);
        $phases = $this->structure->phasesOf($ruleSet);
        $actions = $this->structure->actionsOf($ruleSet);
        $mechanics = $this->structure->mechanicsOf($ruleSet);
        $transitions = $this->structure->transitionsOf($ruleSet);
        $conditions = $this->structure->conditionsOf($ruleSet);
        $groups = $this->structure->conditionGroupsOf($ruleSet);
        $triggers = $this->structure->triggersOf($ruleSet);
        $requirements = $this->structure->requirementsOf($ruleSet);
        $effects = $this->structure->effectsOf($ruleSet);
        $references = $this->structure->referencesOf($ruleSet);

        $phaseIds = array_values($phases->map(fn (GamePhase $phase): string => (string) $phase->getKey())->all());

        /*
         * `values()` before `all()` throughout. An Eloquent collection loaded by a
         * query is already keyed 0..n, so the two are the same array — but the
         * checks below take lists, and re-indexing here is what makes that true by
         * construction rather than by assumption about how the rows arrived.
         */
        $ruleList = array_values($rules->all());
        $phaseList = array_values($phases->all());
        $actionList = array_values($actions->all());
        $transitionList = array_values($transitions->all());
        $conditionList = array_values($conditions->all());
        $groupList = array_values($groups->all());
        $triggerList = array_values($triggers->all());
        $requirementList = array_values($requirements->all());
        $effectList = array_values($effects->all());

        $findings = [
            ...$this->checkTheSetIsStarted($ruleList, $phaseList, $actionList, array_values($mechanics->all())),
            ...$this->checkTheGameCanStartAndFinish($ruleSet, $ruleList, $phaseList),
            ...$this->checkRuleHierarchy($ruleSet, $ruleList),
            ...$this->checkRuleDescriptions($ruleList),
            ...$this->checkRuleReferences($ruleSet, array_values($references->all())),
            ...$this->checkPhaseHierarchy($ruleSet, $phaseList),
            ...$this->checkTransitions($transitionList, $phaseIds),
            ...$this->checkPhaseFlow($ruleSet, $phaseList, $transitionList),
            ...$this->checkActions($actionList),
            ...$this->checkConditions($conditionList, $transitionList, $groupList, $ruleSet),
            ...$this->checkConditionGroups($groupList),
            ...$this->checkTriggers($triggerList, $transitionList),
            ...$this->checkOwnership($requirementList, $effectList),
            ...$this->checkEffectValues($effectList),
            ...$this->checkOutcomes($ruleSet),
            ...$this->checkEconomyReferences($ruleSet, $actionList, $requirementList, $effectList),
        ];

        usort(
            $findings,
            fn (ValidationError $a, ValidationError $b): int => $a->severity()->weight() <=> $b->severity()->weight(),
        );

        return $findings;
    }

    /**
     * Notice a rule set that is still empty.
     *
     * @param  list<GameRule>  $rules
     * @param  list<GamePhase>  $phases
     * @param  list<RuleAction>  $actions
     * @param  list<RuleMechanic>  $mechanics
     * @return list<ValidationError>
     */
    private function checkTheSetIsStarted(array $rules, array $phases, array $actions, array $mechanics): array
    {
        $findings = [];

        if ($rules === []) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoRules,
                __('Rules'),
                __('No rules have been written down yet.'),
            );
        }

        if ($phases === []) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoPhases,
                __('Phases'),
                __('The game has no phases, so there is no shape to a turn.'),
            );
        }

        if ($actions === []) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoActions,
                __('Actions'),
                __('No actions are defined, so players cannot do anything.'),
            );
        }

        if ($mechanics === []) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoMechanics,
                __('Mechanics'),
                __('No mechanics are named. Naming them makes the rules easier to read.'),
            );
        }

        return $findings;
    }

    /**
     * Notice a game with no beginning, no way to win, or no way to stop.
     *
     * Setup counts either way round: a phase of that type, or a rule filed under
     * it. Studios do this both ways and neither is wrong.
     *
     * @param  list<GameRule>  $rules
     * @param  list<GamePhase>  $phases
     * @return list<ValidationError>
     */
    private function checkTheGameCanStartAndFinish(RuleSet $ruleSet, array $rules, array $phases): array
    {
        $findings = [];

        $hasSetupPhase = array_filter($phases, fn (GamePhase $phase): bool => $phase->phase_type === GamePhaseType::Setup) !== [];
        $hasSetupRule = array_filter($rules, fn (GameRule $rule): bool => $rule->rule_type === RuleType::Setup) !== [];

        if ($phases !== [] && ! $hasSetupPhase && ! $hasSetupRule) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoSetup,
                __('Setup'),
                __('Nothing says how the game is set up.'),
            );
        }

        if ($this->structure->victoryConditionsOf($ruleSet)->isEmpty()) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoVictoryCondition,
                __('Victory'),
                __('No victory condition has been recorded.'),
            );
        }

        if ($this->structure->endConditionsOf($ruleSet)->isEmpty()) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::RuleSetHasNoEndCondition,
                __('Game end'),
                __('Nothing brings the game to a close.'),
            );
        }

        return $findings;
    }

    /**
     * Notice a rule that is its own ancestor.
     *
     * @param  list<GameRule>  $rules
     * @return list<ValidationError>
     */
    private function checkRuleHierarchy(RuleSet $ruleSet, array $rules): array
    {
        $findings = [];
        $byId = $this->keyById($rules);

        foreach ($rules as $rule) {
            if ($rule->parent_rule_id === $rule->getKey()) {
                $findings[] = ValidationError::about(
                    ValidationCode::RuleIsItsOwnParent,
                    RuleEntityType::Rule,
                    $rule->getKey(),
                    $rule->name,
                    __('Rule ":rule" is its own parent.', ['rule' => $rule->name]),
                );
            }
        }

        foreach ($this->cycles->findLoopingNodes($this->structure->ruleParentMap($ruleSet)) as $ruleId) {
            $rule = $byId[$ruleId] ?? null;

            if ($rule === null || $rule->parent_rule_id === $ruleId) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::RuleHierarchyIsCircular,
                RuleEntityType::Rule,
                $ruleId,
                $rule->name,
                __('Following the parents of ":rule" leads back to itself.', ['rule' => $rule->name]),
            );
        }

        return $findings;
    }

    /**
     * Notice a rule that is only a heading.
     *
     * A rule with children is exempt: "Combat" is allowed to be a heading, and
     * the four rules under it are where the words live.
     *
     * @param  list<GameRule>  $rules
     * @return list<ValidationError>
     */
    private function checkRuleDescriptions(array $rules): array
    {
        $parentIds = [];

        foreach ($rules as $rule) {
            if ($rule->parent_rule_id !== null) {
                $parentIds[$rule->parent_rule_id] = true;
            }
        }

        $findings = [];

        foreach ($rules as $rule) {
            if (! $rule->isInPlay() || isset($parentIds[$rule->getKey()])) {
                continue;
            }

            if ($rule->description === null || trim($rule->description) === '') {
                $findings[] = ValidationError::about(
                    ValidationCode::RuleHasNoDescription,
                    RuleEntityType::Rule,
                    $rule->getKey(),
                    $rule->name,
                    __('Rule ":rule" has a name but no wording.', ['rule' => $rule->name]),
                );
            }
        }

        return $findings;
    }

    /**
     * Notice a rule pointing at itself, and loops among the directed references.
     *
     * @param  list<RuleReference>  $references
     * @return list<ValidationError>
     */
    private function checkRuleReferences(RuleSet $ruleSet, array $references): array
    {
        $findings = [];

        foreach ($references as $reference) {
            if ($reference->rule_id !== $reference->referenced_rule_id) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::RuleReferencesItself,
                RuleEntityType::Reference,
                $reference->getKey(),
                $reference->rule->name,
                __('Rule ":rule" references itself.', ['rule' => $reference->rule->name]),
            );
        }

        $names = [];

        foreach ($references as $reference) {
            if ($reference->rule !== null) {
                $names[$reference->rule_id] = $reference->rule->name;
            }

            if ($reference->referencedRule !== null) {
                $names[$reference->referenced_rule_id] = $reference->referencedRule->name;
            }
        }

        foreach ($this->cycles->findLoopingEdges($this->structure->directedReferenceMap($ruleSet)) as $ruleId) {
            $findings[] = ValidationError::about(
                ValidationCode::RuleReferenceIsCircular,
                RuleEntityType::Rule,
                $ruleId,
                $names[$ruleId] ?? __('Rule'),
                __('Following the references from ":rule" leads back to itself.', [
                    'rule' => $names[$ruleId] ?? '',
                ]),
            );
        }

        return $findings;
    }

    /**
     * Notice a phase that is its own ancestor.
     *
     * @param  list<GamePhase>  $phases
     * @return list<ValidationError>
     */
    private function checkPhaseHierarchy(RuleSet $ruleSet, array $phases): array
    {
        $byId = $this->keyById($phases);
        $findings = [];

        foreach ($this->cycles->findLoopingNodes($this->structure->phaseParentMap($ruleSet)) as $phaseId) {
            $phase = $byId[$phaseId] ?? null;

            $findings[] = ValidationError::about(
                ValidationCode::PhaseHierarchyIsCircular,
                RuleEntityType::Phase,
                $phaseId,
                $phase->name ?? __('Phase'),
                __('Following the parents of ":phase" leads back to itself.', [
                    'phase' => $phase->name ?? '',
                ]),
            );
        }

        return $findings;
    }

    /**
     * Notice transitions that cross rule sets or go nowhere.
     *
     * Both are errors. The first should be unreachable through the screens —
     * `RuleCatalogue` resolves each end through the set — and is checked anyway,
     * because a clone or a restore could produce one and nothing downstream would
     * make sense of it.
     *
     * @param  list<PhaseTransition>  $transitions
     * @param  list<string>  $phaseIds
     * @return list<ValidationError>
     */
    private function checkTransitions(array $transitions, array $phaseIds): array
    {
        $findings = [];

        foreach ($transitions as $transition) {
            $label = $this->describeTransition($transition);

            $fromIsForeign = ! in_array($transition->from_phase_id, $phaseIds, strict: true);
            $toIsForeign = ! in_array($transition->to_phase_id, $phaseIds, strict: true);

            if ($fromIsForeign || $toIsForeign) {
                $findings[] = ValidationError::about(
                    ValidationCode::TransitionLeavesTheRuleSet,
                    RuleEntityType::Transition,
                    $transition->getKey(),
                    $label,
                    __('Transition ":transition" names a phase from another rule set.', ['transition' => $label]),
                );
            }

            if ($transition->from_phase_id === $transition->to_phase_id) {
                $findings[] = ValidationError::about(
                    ValidationCode::TransitionLoopsOnOnePhase,
                    RuleEntityType::Transition,
                    $transition->getKey(),
                    $label,
                    __('Transition ":transition" leads back to the phase it starts from.', ['transition' => $label]),
                );
            }
        }

        return $findings;
    }

    /**
     * Notice phases play cannot leave, and phases play never reaches.
     *
     * Terminal phases are exempt from the first: a phase the game ends in is
     * supposed to be a dead end.
     *
     * @param  list<GamePhase>  $phases
     * @param  list<PhaseTransition>  $transitions
     * @return list<ValidationError>
     */
    private function checkPhaseFlow(RuleSet $ruleSet, array $phases, array $transitions): array
    {
        if ($phases === []) {
            return [];
        }

        $findings = [];
        $hasExit = [];

        foreach ($transitions as $transition) {
            $hasExit[$transition->from_phase_id] = true;
        }

        foreach ($phases as $phase) {
            if (! $phase->isInPlay()) {
                continue;
            }

            if ($phase->isTerminal() || isset($hasExit[$phase->getKey()])) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::PhaseHasNoOutgoingTransition,
                RuleEntityType::Phase,
                $phase->getKey(),
                $phase->name,
                __('Play arrives at ":phase" and has nowhere to go next.', ['phase' => $phase->name]),
            );
        }

        $byId = $this->keyById($phases);

        foreach ($this->graphs->build($ruleSet)->unreachable as $phaseId) {
            $phase = $byId[$phaseId] ?? null;

            if ($phase === null || ! $phase->isInPlay()) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::PhaseIsUnreachable,
                RuleEntityType::Phase,
                $phaseId,
                $phase->name,
                __('No transition leads into ":phase", so play never arrives there.', ['phase' => $phase->name]),
            );
        }

        return $findings;
    }

    /**
     * Notice actions nobody can take, and actions that do nothing.
     *
     * @param  list<RuleAction>  $actions
     * @return list<ValidationError>
     */
    private function checkActions(array $actions): array
    {
        $findings = [];

        foreach ($actions as $action) {
            if (! $action->isInPlay()) {
                continue;
            }

            if ($action->phase_id === null) {
                $findings[] = ValidationError::about(
                    ValidationCode::ActionHasNoPhase,
                    RuleEntityType::Action,
                    $action->getKey(),
                    $action->name,
                    __('Action ":action" has no phase, so nobody can place it in the turn.', ['action' => $action->name]),
                );
            }

            if (($action->effects_count ?? 0) === 0) {
                $findings[] = ValidationError::about(
                    ValidationCode::ActionHasNoEffect,
                    RuleEntityType::Action,
                    $action->getKey(),
                    $action->name,
                    __('Action ":action" changes nothing when it is taken.', ['action' => $action->name]),
                );
            }

            if (($action->requirements_count ?? 0) === 0) {
                $findings[] = ValidationError::about(
                    ValidationCode::ActionHasNoRequirement,
                    RuleEntityType::Action,
                    $action->getKey(),
                    $action->name,
                    __('Action ":action" can always be taken, so it carries no decision.', ['action' => $action->name]),
                );
            }
        }

        return $findings;
    }

    /**
     * Notice conditions that are incomplete, nonsensical or unused.
     *
     * The numeric check is the one that earns its keep: "rounds elapsed is more
     * than blue" is a sentence somebody typed by accident, and nothing else in
     * the module would ever notice.
     *
     * @param  list<RuleCondition>  $conditions
     * @param  list<PhaseTransition>  $transitions
     * @param  list<ConditionGroup>  $groups
     * @return list<ValidationError>
     */
    private function checkConditions(array $conditions, array $transitions, array $groups, RuleSet $ruleSet): array
    {
        $findings = [];
        $used = [];

        foreach ($transitions as $transition) {
            if ($transition->condition_id !== null) {
                $used[$transition->condition_id] = true;
            }
        }

        foreach ($groups as $group) {
            foreach ($group->conditions as $condition) {
                $used[$condition->getKey()] = true;
            }
        }

        foreach ([
            ...array_values($this->structure->victoryConditionsOf($ruleSet)->all()),
            ...array_values($this->structure->defeatConditionsOf($ruleSet)->all()),
            ...array_values($this->structure->endConditionsOf($ruleSet)->all()),
        ] as $outcome) {
            if ($outcome->condition_id !== null) {
                $used[$outcome->condition_id] = true;
            }
        }

        foreach ($conditions as $condition) {
            if (! $condition->hasRequiredValue()) {
                $findings[] = ValidationError::about(
                    ValidationCode::ConditionHasNoValue,
                    RuleEntityType::Condition,
                    $condition->getKey(),
                    $condition->name,
                    __('Condition ":condition" compares against nothing.', ['condition' => $condition->name]),
                );
            }

            if (
                $condition->operator->expectsNumber()
                && $condition->value !== null
                && $condition->value !== ''
                && ! is_numeric($condition->value)
            ) {
                $findings[] = ValidationError::about(
                    ValidationCode::ConditionValueIsNotNumeric,
                    RuleEntityType::Condition,
                    $condition->getKey(),
                    $condition->name,
                    __('Condition ":condition" compares ":operator" against text.', [
                        'condition' => $condition->name,
                        'operator' => $condition->operator->label(),
                    ]),
                );
            }

            if (! isset($used[$condition->getKey()])) {
                $findings[] = ValidationError::about(
                    ValidationCode::ConditionIsUnused,
                    RuleEntityType::Condition,
                    $condition->getKey(),
                    $condition->name,
                    __('Nothing points at condition ":condition".', ['condition' => $condition->name]),
                );
            }
        }

        return $findings;
    }

    /**
     * Notice groups with nothing in them.
     *
     * @param  list<ConditionGroup>  $groups
     * @return list<ValidationError>
     */
    private function checkConditionGroups(array $groups): array
    {
        $findings = [];

        foreach ($groups as $group) {
            if ($group->conditions->isNotEmpty()) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::ConditionGroupIsEmpty,
                RuleEntityType::ConditionGroup,
                $group->getKey(),
                $group->name,
                __('Group ":group" holds no conditions.', ['group' => $group->name]),
            );
        }

        return $findings;
    }

    /**
     * Notice triggers nothing points at.
     *
     * @param  list<RuleTrigger>  $triggers
     * @param  list<PhaseTransition>  $transitions
     * @return list<ValidationError>
     */
    private function checkTriggers(array $triggers, array $transitions): array
    {
        $used = [];

        foreach ($transitions as $transition) {
            if ($transition->trigger_id !== null) {
                $used[$transition->trigger_id] = true;
            }
        }

        $findings = [];

        foreach ($triggers as $trigger) {
            if (isset($used[$trigger->getKey()])) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::TriggerIsUnused,
                RuleEntityType::Trigger,
                $trigger->getKey(),
                $trigger->name,
                __('Nothing points at trigger ":trigger".', ['trigger' => $trigger->name]),
            );
        }

        return $findings;
    }

    /**
     * Notice requirements and effects that belong to nothing.
     *
     * Errors, because neither is ever checked or carried out. The commands refuse
     * to create one; this catches the shapes that predate the check.
     *
     * @param  list<RuleRequirement>  $requirements
     * @param  list<RuleEffect>  $effects
     * @return list<ValidationError>
     */
    private function checkOwnership(array $requirements, array $effects): array
    {
        $findings = [];

        foreach ($requirements as $requirement) {
            if ($requirement->hasExactlyOneOwner()) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::RequirementHasNoOwner,
                RuleEntityType::Requirement,
                $requirement->getKey(),
                $this->shorten($requirement->description),
                __('This requirement is not attached to a rule or an action.'),
            );
        }

        foreach ($effects as $effect) {
            if ($effect->hasExactlyOneOwner()) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::EffectHasNoOwner,
                RuleEntityType::Effect,
                $effect->getKey(),
                $effect->target,
                __('This effect is not attached to a rule or an action.'),
            );
        }

        return $findings;
    }

    /**
     * Notice effects whose type implies an amount they do not carry.
     *
     * @param  list<RuleEffect>  $effects
     * @return list<ValidationError>
     */
    private function checkEffectValues(array $effects): array
    {
        $findings = [];

        foreach ($effects as $effect) {
            if ($effect->hasRequiredValue()) {
                continue;
            }

            $findings[] = ValidationError::about(
                ValidationCode::EffectHasNoValue,
                RuleEntityType::Effect,
                $effect->getKey(),
                $effect->target,
                __('":effect" on :target does not say how much.', [
                    'effect' => $effect->effect_type->label(),
                    'target' => $effect->target,
                ]),
            );
        }

        return $findings;
    }

    /**
     * Notice outcomes nobody can measure.
     *
     * @return list<ValidationError>
     */
    private function checkOutcomes(RuleSet $ruleSet): array
    {
        $findings = [];

        foreach ($this->structure->victoryConditionsOf($ruleSet) as $outcome) {
            if (! $outcome->isMeasurable()) {
                $findings[] = ValidationError::about(
                    ValidationCode::VictoryConditionHasNoCondition,
                    RuleEntityType::VictoryCondition,
                    $outcome->getKey(),
                    $outcome->name,
                    __('Victory condition ":outcome" has no condition to measure it.', ['outcome' => $outcome->name]),
                );
            }
        }

        foreach ($this->structure->defeatConditionsOf($ruleSet) as $outcome) {
            if (! $outcome->isMeasurable()) {
                $findings[] = ValidationError::about(
                    ValidationCode::DefeatConditionHasNoCondition,
                    RuleEntityType::DefeatCondition,
                    $outcome->getKey(),
                    $outcome->name,
                    __('Defeat condition ":outcome" has no condition to measure it.', ['outcome' => $outcome->name]),
                );
            }
        }

        foreach ($this->structure->endConditionsOf($ruleSet) as $outcome) {
            if (! $outcome->isMeasurable()) {
                $findings[] = ValidationError::about(
                    ValidationCode::GameEndConditionHasNoCondition,
                    RuleEntityType::GameEndCondition,
                    $outcome->getKey(),
                    $outcome->name,
                    __('End condition ":outcome" has no condition to measure it.', ['outcome' => $outcome->name]),
                );
            }
        }

        return $findings;
    }

    /**
     * Notice handles that name nothing in the game's economy.
     *
     * Only reported when the design state has an active balance profile at all.
     * Telling a studio that has not modelled an economy that every one of their
     * references is broken would be noise rather than a finding, so the adapter
     * answers with an empty list in that case and this check goes quiet.
     *
     * @param  list<RuleAction>  $actions
     * @param  list<RuleRequirement>  $requirements
     * @param  list<RuleEffect>  $effects
     * @return list<ValidationError>
     */
    private function checkEconomyReferences(RuleSet $ruleSet, array $actions, array $requirements, array $effects): array
    {
        $version = $ruleSet->version;

        if ($version === null) {
            return [];
        }

        $actionHandles = [];

        foreach ($actions as $action) {
            if ($action->economy_action_slug !== null) {
                $actionHandles[] = $action->economy_action_slug;
            }
        }

        $resourceHandles = [];

        foreach ($requirements as $requirement) {
            if ($requirement->economy_resource_slug !== null) {
                $resourceHandles[] = $requirement->economy_resource_slug;
            }
        }

        foreach ($effects as $effect) {
            if ($effect->economy_resource_slug !== null) {
                $resourceHandles[] = $effect->economy_resource_slug;
            }
        }

        $findings = [];

        foreach ($this->economy->unresolvedHandles($version, $actionHandles, $resourceHandles) as $handle) {
            $findings[] = ValidationError::aboutRuleSet(
                ValidationCode::EconomyReferenceIsUnresolved,
                $handle,
                __('":handle" is not in this version\'s balance profile.', ['handle' => $handle]),
            );
        }

        return $findings;
    }

    /**
     * Describe a transition well enough to click on.
     */
    private function describeTransition(PhaseTransition $transition): string
    {
        return trim(sprintf(
            '%s → %s',
            $transition->fromPhase->name,
            $transition->toPhase->name,
        ));
    }

    /**
     * The first few words of a body of prose, for a finding's subject line.
     */
    private function shorten(string $text): string
    {
        $trimmed = trim($text);

        return mb_strlen($trimmed) <= 60 ? $trimmed : mb_substr($trimmed, 0, 57).'…';
    }

    /**
     * Index a list of records by their primary key.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  list<TModel>  $records
     * @return array<string, TModel>
     */
    private function keyById(array $records): array
    {
        $indexed = [];

        foreach ($records as $record) {
            $indexed[(string) $record->getKey()] = $record;
        }

        return $indexed;
    }
}
