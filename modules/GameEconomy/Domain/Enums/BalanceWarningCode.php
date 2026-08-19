<?php

namespace Modules\GameEconomy\Domain\Enums;

/**
 * The things the analysis knows how to notice.
 *
 * Every finding the module can report is a case here, which is what makes the
 * analysis deterministic in the sense section 27 means: the same configuration
 * produces the same list, every time, with no heuristics and no scoring. A
 * designer can therefore learn what each of these means once and trust it —
 * which is the difference between a check somebody acts on and a check somebody
 * turns off.
 *
 * The severity travels with the code rather than being decided at the call site,
 * so "an uncapped resource is a warning, not an error" has exactly one
 * definition. Errors are reserved for shapes that cannot be a working economy
 * under any reading; everything a designer might have meant on purpose is a
 * warning.
 *
 * None of this refuses a save. See {@see BalanceWarningSeverity}.
 */
enum BalanceWarningCode: string
{
    case ProfileHasNoResources = 'profile_has_no_resources';
    case ProfileHasNoActions = 'profile_has_no_actions';

    case ResourceHasNoGeneration = 'resource_has_no_generation';
    case ResourceHasNoConsumption = 'resource_has_no_consumption';
    case ResourceAccumulatesWithoutSink = 'resource_accumulates_without_sink';
    case ResourceGenerationIsUncapped = 'resource_generation_is_uncapped';
    case ResourceStartsOutsideItsRange = 'resource_starts_outside_its_range';

    case ActionHasNoCost = 'action_has_no_cost';
    case ActionHasNoOutcome = 'action_has_no_outcome';
    case ActionRewardExceedsMaximum = 'action_reward_exceeds_maximum';
    case ActionMultipliesAResource = 'action_multiplies_a_resource';

    case FlowHasNoAmount = 'flow_has_no_amount';
    case VariableAmountHasNoRange = 'variable_amount_has_no_range';

    case VariableIsOutsideItsRange = 'variable_is_outside_its_range';
    case ProbabilityIsOutsideZeroToOne = 'probability_is_outside_zero_to_one';

    /**
     * How seriously to take this finding.
     */
    public function severity(): BalanceWarningSeverity
    {
        return match ($this) {
            self::ProfileHasNoResources,
            self::ResourceHasNoGeneration,
            self::ResourceStartsOutsideItsRange,
            self::VariableIsOutsideItsRange,
            self::ProbabilityIsOutsideZeroToOne => BalanceWarningSeverity::Error,

            self::ProfileHasNoActions,
            self::ResourceHasNoConsumption,
            self::ResourceAccumulatesWithoutSink,
            self::ResourceGenerationIsUncapped,
            self::ActionHasNoCost,
            self::ActionHasNoOutcome,
            self::ActionRewardExceedsMaximum,
            self::ActionMultipliesAResource,
            self::VariableAmountHasNoRange => BalanceWarningSeverity::Warning,

            self::FlowHasNoAmount => BalanceWarningSeverity::Info,
        };
    }

    /**
     * A short heading for the finding, worded without naming anything.
     *
     * The subject goes in the description, which is built per finding — so this
     * stays a phrase somebody can group a list by.
     */
    public function title(): string
    {
        return match ($this) {
            self::ProfileHasNoResources => __('No resources'),
            self::ProfileHasNoActions => __('No actions'),
            self::ResourceHasNoGeneration => __('Resource has no source'),
            self::ResourceHasNoConsumption => __('Resource has no sink'),
            self::ResourceAccumulatesWithoutSink => __('Resource only accumulates'),
            self::ResourceGenerationIsUncapped => __('Uncapped generation'),
            self::ResourceStartsOutsideItsRange => __('Starting value out of range'),
            self::ActionHasNoCost => __('Action costs nothing'),
            self::ActionHasNoOutcome => __('Action does nothing'),
            self::ActionRewardExceedsMaximum => __('Reward exceeds the maximum'),
            self::ActionMultipliesAResource => __('Action multiplies a resource'),
            self::FlowHasNoAmount => __('Flow moves nothing'),
            self::VariableAmountHasNoRange => __('Variable amount has no range'),
            self::VariableIsOutsideItsRange => __('Value out of range'),
            self::ProbabilityIsOutsideZeroToOne => __('Probability out of bounds'),
        };
    }

    /**
     * Why this shape is worth pointing at.
     *
     * Written as the general case. Each finding carries its own sentence naming
     * the resource, action or variable it is about; this is what the designer
     * reads when they want to know why the check exists at all.
     */
    public function explanation(): string
    {
        return match ($this) {
            self::ProfileHasNoResources => __('An economy with nothing in it cannot be analysed. Add the resources players hold and spend.'),
            self::ProfileHasNoActions => __('Nothing changes the economy yet, so every resource sits still. Add the actions players take.'),
            self::ResourceHasNoGeneration => __('Nothing puts this resource into the game, so nobody can ever spend it.'),
            self::ResourceHasNoConsumption => __('Nothing spends this resource, so holding it costs a player nothing.'),
            self::ResourceAccumulatesWithoutSink => __('This resource arrives and never leaves, so it grows without limit as the game goes on.'),
            self::ResourceGenerationIsUncapped => __('This resource is produced with no maximum set, so nothing bounds how much a player can hold.'),
            self::ResourceStartsOutsideItsRange => __('Players start with an amount the resource itself does not allow.'),
            self::ActionHasNoCost => __('This action is free, so there is no reason not to take it every turn.'),
            self::ActionHasNoOutcome => __('This action pays nothing and changes nothing, so nobody would take it.'),
            self::ActionRewardExceedsMaximum => __('This action pays out more than the resource allows a player to hold, so part of the reward is lost.'),
            self::ActionMultipliesAResource => __('This action returns more of a resource than it takes, so repeating it produces value from nothing.'),
            self::FlowHasNoAmount => __('This flow moves nothing, so it has no effect on the economy as configured.'),
            self::VariableAmountHasNoRange => __('This amount is marked as variable but no range was given, so the analysis cannot tell how far it swings.'),
            self::VariableIsOutsideItsRange => __('This value falls outside the range the designer set for it.'),
            self::ProbabilityIsOutsideZeroToOne => __('Probabilities are written between 0 and 1 throughout, and this one is not.'),
        };
    }
}
