<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * How settled one rule, phase or action is.
 *
 * Separate from the rule set's own status, and about something else entirely.
 * The set's status says whether the whole system may be edited; this says how
 * much the designer currently believes in one piece of it.
 *
 * Deprecated is the case that earns the enum. Rules do not stop existing when a
 * studio decides against them — they stop applying, and the record of having
 * tried them is exactly what somebody reading the design six months later needs.
 * A deprecated rule stays in the tree, greyed, and the validator ignores it.
 */
enum RuleStatus: string implements Described
{
    case Draft = 'draft';
    case Active = 'active';
    case Deprecated = 'deprecated';

    /**
     * The status a rule starts life in.
     *
     * Active rather than draft, which is the opposite of the rule set around it.
     * A rule written inside a draft rule set is already part of that draft;
     * making somebody promote every rule individually would be a second
     * lifecycle nobody asked for.
     */
    public static function default(): self
    {
        return self::Active;
    }

    /**
     * Determine whether this piece still applies to play.
     */
    public function isInPlay(): bool
    {
        return $this !== self::Deprecated;
    }

    /**
     * A human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Active'),
            self::Deprecated => __('Deprecated'),
        };
    }

    /**
     * What the status says about the rule.
     */
    public function description(): string
    {
        return match ($this) {
            self::Draft => __('Written down, but not yet part of the game.'),
            self::Active => __('Part of the game as it currently stands.'),
            self::Deprecated => __('Kept for the record; no longer applies.'),
        };
    }
}
