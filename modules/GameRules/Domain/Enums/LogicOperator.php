<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * How the conditions in a group combine.
 *
 * Two cases, and section 19 of the brief is firm that it stays two: a group is a
 * flat list of conditions joined by one operator, not an expression tree.
 *
 * The restraint is deliberate rather than provisional. An arbitrary tree of ANDs
 * and ORs needs a parser, a renderer and a precedence rule, and by the time a
 * studio needs one they need an engine that can evaluate it too. A flat group
 * covers what a board game rule usually says — "when all players have passed and
 * the deck is empty" — and stays readable in a form somebody fills in.
 */
enum LogicOperator: string implements Described
{
    case And = 'and';
    case Or = 'or';

    /**
     * The operator a group uses when nobody chose one.
     */
    public static function default(): self
    {
        return self::And;
    }

    /**
     * A human readable label for the operator.
     */
    public function label(): string
    {
        return match ($this) {
            self::And => __('All of these'),
            self::Or => __('Any of these'),
        };
    }

    /**
     * What the operator means for the group.
     */
    public function description(): string
    {
        return match ($this) {
            self::And => __('Every condition in the group has to hold.'),
            self::Or => __('At least one condition in the group has to hold.'),
        };
    }

    /**
     * The word drawn between two conditions in a builder.
     */
    public function joiner(): string
    {
        return match ($this) {
            self::And => __('and'),
            self::Or => __('or'),
        };
    }
}
