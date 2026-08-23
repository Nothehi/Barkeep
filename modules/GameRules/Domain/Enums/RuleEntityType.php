<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Labelled;

/**
 * What kind of record a finding or a graph node is about.
 *
 * Exists so the interface can link a finding into the right list without parsing
 * its message, and so the graph can tell a phase node from an action node
 * without a second field saying so.
 */
enum RuleEntityType: string implements Labelled
{
    case RuleSet = 'rule_set';
    case Rule = 'rule';
    case Mechanic = 'mechanic';
    case Phase = 'phase';
    case Transition = 'transition';
    case Action = 'action';
    case Requirement = 'requirement';
    case Condition = 'condition';
    case ConditionGroup = 'condition_group';
    case Effect = 'effect';
    case Trigger = 'trigger';
    case VictoryCondition = 'victory_condition';
    case DefeatCondition = 'defeat_condition';
    case GameEndCondition = 'game_end_condition';
    case Reference = 'reference';

    /**
     * A human readable label for the kind of record.
     */
    public function label(): string
    {
        return match ($this) {
            self::RuleSet => __('Rule set'),
            self::Rule => __('Rule'),
            self::Mechanic => __('Mechanic'),
            self::Phase => __('Phase'),
            self::Transition => __('Transition'),
            self::Action => __('Action'),
            self::Requirement => __('Requirement'),
            self::Condition => __('Condition'),
            self::ConditionGroup => __('Condition group'),
            self::Effect => __('Effect'),
            self::Trigger => __('Trigger'),
            self::VictoryCondition => __('Victory condition'),
            self::DefeatCondition => __('Defeat condition'),
            self::GameEndCondition => __('Game end condition'),
            self::Reference => __('Rule reference'),
        };
    }
}
