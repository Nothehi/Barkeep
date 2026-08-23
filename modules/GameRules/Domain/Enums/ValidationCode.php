<?php

namespace Modules\GameRules\Domain\Enums;

/**
 * Everything the validator knows how to notice.
 *
 * Every finding the module can report is a case here, which is what makes static
 * validation deterministic: the same rule set produces the same list every time,
 * from checks a designer can learn once and then trust. There is no scoring, no
 * heuristic and no threshold anybody has to tune — which is the difference
 * between a check somebody acts on and a check somebody turns off.
 *
 * The severity travels with the code rather than being decided at the call site,
 * so "an action with no phase is an error" has exactly one definition. Errors are
 * reserved for shapes that cannot be a working rule system under any reading;
 * everything a designer might have meant on purpose, or simply not reached yet,
 * is a warning.
 *
 * Adding a check means adding a case here and a method to the validator, in that
 * order. The enum is the published list.
 */
enum ValidationCode: string
{
    case RuleSetHasNoRules = 'rule_set_has_no_rules';
    case RuleSetHasNoPhases = 'rule_set_has_no_phases';
    case RuleSetHasNoActions = 'rule_set_has_no_actions';
    case RuleSetHasNoMechanics = 'rule_set_has_no_mechanics';
    case RuleSetHasNoSetup = 'rule_set_has_no_setup';
    case RuleSetHasNoVictoryCondition = 'rule_set_has_no_victory_condition';
    case RuleSetHasNoEndCondition = 'rule_set_has_no_end_condition';

    case RuleIsItsOwnParent = 'rule_is_its_own_parent';
    case RuleHierarchyIsCircular = 'rule_hierarchy_is_circular';
    case RuleReferencesItself = 'rule_references_itself';
    case RuleReferenceIsCircular = 'rule_reference_is_circular';
    case RuleHasNoDescription = 'rule_has_no_description';

    case PhaseHierarchyIsCircular = 'phase_hierarchy_is_circular';
    case PhaseHasNoOutgoingTransition = 'phase_has_no_outgoing_transition';
    case PhaseIsUnreachable = 'phase_is_unreachable';
    case TransitionLeavesTheRuleSet = 'transition_leaves_the_rule_set';
    case TransitionLoopsOnOnePhase = 'transition_loops_on_one_phase';

    case ActionHasNoPhase = 'action_has_no_phase';
    case ActionHasNoEffect = 'action_has_no_effect';
    case ActionHasNoRequirement = 'action_has_no_requirement';

    case ConditionHasNoValue = 'condition_has_no_value';
    case ConditionValueIsNotNumeric = 'condition_value_is_not_numeric';
    case ConditionIsUnused = 'condition_is_unused';
    case ConditionGroupIsEmpty = 'condition_group_is_empty';

    case EffectHasNoValue = 'effect_has_no_value';
    case EffectHasNoOwner = 'effect_has_no_owner';
    case RequirementHasNoOwner = 'requirement_has_no_owner';

    case TriggerIsUnused = 'trigger_is_unused';

    case VictoryConditionHasNoCondition = 'victory_condition_has_no_condition';
    case DefeatConditionHasNoCondition = 'defeat_condition_has_no_condition';
    case GameEndConditionHasNoCondition = 'game_end_condition_has_no_condition';

    case EconomyReferenceIsUnresolved = 'economy_reference_is_unresolved';

    /**
     * How seriously to take this finding.
     *
     * Errors are the shapes that make a rule set self-contradictory or
     * impossible to read: a hierarchy that loops, a transition pointing outside
     * the set, an action nobody can ever take because it belongs to no phase.
     * Everything else is a warning.
     */
    public function severity(): ValidationSeverity
    {
        return match ($this) {
            self::RuleIsItsOwnParent,
            self::RuleHierarchyIsCircular,
            self::RuleReferencesItself,
            self::RuleReferenceIsCircular,
            self::PhaseHierarchyIsCircular,
            self::TransitionLeavesTheRuleSet,
            self::TransitionLoopsOnOnePhase,
            self::ActionHasNoPhase,
            self::EffectHasNoOwner,
            self::RequirementHasNoOwner => ValidationSeverity::Error,

            default => ValidationSeverity::Warning,
        };
    }

    /**
     * The heading this finding is listed under.
     */
    public function title(): string
    {
        return match ($this) {
            self::RuleSetHasNoRules => __('No rules yet'),
            self::RuleSetHasNoPhases => __('No phases yet'),
            self::RuleSetHasNoActions => __('No actions yet'),
            self::RuleSetHasNoMechanics => __('No mechanics named'),
            self::RuleSetHasNoSetup => __('Nothing says how to start'),
            self::RuleSetHasNoVictoryCondition => __('No way to win'),
            self::RuleSetHasNoEndCondition => __('No way to finish'),

            self::RuleIsItsOwnParent => __('Rule contains itself'),
            self::RuleHierarchyIsCircular => __('Circular rule hierarchy'),
            self::RuleReferencesItself => __('Rule references itself'),
            self::RuleReferenceIsCircular => __('Circular rule reference'),
            self::RuleHasNoDescription => __('Rule has no description'),

            self::PhaseHierarchyIsCircular => __('Circular phase hierarchy'),
            self::PhaseHasNoOutgoingTransition => __('Phase has no exit'),
            self::PhaseIsUnreachable => __('Phase is unreachable'),
            self::TransitionLeavesTheRuleSet => __('Transition leaves the rule set'),
            self::TransitionLoopsOnOnePhase => __('Transition goes nowhere'),

            self::ActionHasNoPhase => __('Action has no phase'),
            self::ActionHasNoEffect => __('Action does nothing'),
            self::ActionHasNoRequirement => __('Action has no requirements'),

            self::ConditionHasNoValue => __('Condition has no value'),
            self::ConditionValueIsNotNumeric => __('Condition compares against text'),
            self::ConditionIsUnused => __('Condition is never used'),
            self::ConditionGroupIsEmpty => __('Condition group is empty'),

            self::EffectHasNoValue => __('Effect has no amount'),
            self::EffectHasNoOwner => __('Effect belongs to nothing'),
            self::RequirementHasNoOwner => __('Requirement belongs to nothing'),

            self::TriggerIsUnused => __('Trigger is never used'),

            self::VictoryConditionHasNoCondition => __('Victory condition is not measurable'),
            self::DefeatConditionHasNoCondition => __('Defeat condition is not measurable'),
            self::GameEndConditionHasNoCondition => __('End condition is not measurable'),

            self::EconomyReferenceIsUnresolved => __('Economy reference cannot be resolved'),
        };
    }

    /**
     * Why the check exists at all, as opposed to what it found here.
     */
    public function explanation(): string
    {
        return match ($this) {
            self::RuleSetHasNoRules => __('A rule set with no rules describes nothing yet.'),
            self::RuleSetHasNoPhases => __('Without phases there is no shape to a turn or a round.'),
            self::RuleSetHasNoActions => __('If players cannot do anything, there is no game to play.'),
            self::RuleSetHasNoMechanics => __('Naming the mechanisms makes the rules easier to read and to compare.'),
            self::RuleSetHasNoSetup => __('Somebody opening the box needs to be told how to start.'),
            self::RuleSetHasNoVictoryCondition => __('A game nobody can win is a toy. That may be deliberate.'),
            self::RuleSetHasNoEndCondition => __('Without an end condition the game runs forever.'),

            self::RuleIsItsOwnParent => __('A rule cannot contain itself.'),
            self::RuleHierarchyIsCircular => __('Following the parents leads back to where it started, so the tree has no top.'),
            self::RuleReferencesItself => __('A rule cannot depend on, modify or override itself.'),
            self::RuleReferenceIsCircular => __('Following the references leads back to where it started, so neither rule can be read first.'),
            self::RuleHasNoDescription => __('A rule with only a name is a note to self.'),

            self::PhaseHierarchyIsCircular => __('Following the parent phases leads back to where it started.'),
            self::PhaseHasNoOutgoingTransition => __('Play arrives here and has nowhere to go next.'),
            self::PhaseIsUnreachable => __('No transition leads into this phase, so play never arrives.'),
            self::TransitionLeavesTheRuleSet => __('Both ends of a transition have to belong to the same rule set.'),
            self::TransitionLoopsOnOnePhase => __('A transition from a phase to itself does not move play.'),

            self::ActionHasNoPhase => __('An action nobody can place in the turn is an action nobody can take.'),
            self::ActionHasNoEffect => __('An action with no effect changes nothing when it is taken.'),
            self::ActionHasNoRequirement => __('An action anybody can always take is a button with no decision behind it.'),

            self::ConditionHasNoValue => __('This operator compares against something, and nothing was given.'),
            self::ConditionValueIsNotNumeric => __('Comparing more or less only makes sense against a number.'),
            self::ConditionIsUnused => __('Nothing points at this condition, so it never affects play.'),
            self::ConditionGroupIsEmpty => __('A group with no conditions in it holds nothing.'),

            self::EffectHasNoValue => __('This kind of effect is meaningless without an amount.'),
            self::EffectHasNoOwner => __('An effect has to belong to a rule or an action to ever happen.'),
            self::RequirementHasNoOwner => __('A requirement has to belong to a rule or an action to ever be checked.'),

            self::TriggerIsUnused => __('Nothing points at this trigger, so it never fires.'),

            self::VictoryConditionHasNoCondition => __('Without a condition, nobody can tell when it has been met.'),
            self::DefeatConditionHasNoCondition => __('Without a condition, nobody can tell when it has been met.'),
            self::GameEndConditionHasNoCondition => __('Without a condition, nobody can tell when the game is over.'),

            self::EconomyReferenceIsUnresolved => __('This points at a balance record that is not in the version\'s active profile.'),
        };
    }
}
