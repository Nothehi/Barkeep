<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * How one rule relates to another.
 *
 * The edges of the rule graph, and the reason a rule set is more than a list.
 * "Combat overrides Movement" and "Siege is an exception to Combat" are the
 * facts a designer loses first and needs most, and writing them down is what
 * lets the validator notice a cycle before a playtester does.
 *
 * References never cross rule sets. A rule may only point at another rule in the
 * same set, which the repository enforces by resolving both through it — see
 * section 27 of the brief.
 */
enum ReferenceType: string implements Described
{
    case DependsOn = 'depends_on';
    case Modifies = 'modifies';
    case Overrides = 'overrides';
    case ExceptionTo = 'exception_to';
    case RelatedTo = 'related_to';

    /**
     * The type a reference falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::RelatedTo;
    }

    /**
     * Determine whether the relationship has a direction that matters.
     *
     * "Related to" is symmetric — it says the two rules should be read together
     * and nothing about which comes first. The rest are one-way, and only the
     * one-way ones are followed when looking for a cycle: a mutual "related to"
     * is a note, not a contradiction.
     */
    public function isDirected(): bool
    {
        return $this !== self::RelatedTo;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::DependsOn => __('Depends on'),
            self::Modifies => __('Modifies'),
            self::Overrides => __('Overrides'),
            self::ExceptionTo => __('Exception to'),
            self::RelatedTo => __('Related to'),
        };
    }

    /**
     * What the relationship says.
     */
    public function description(): string
    {
        return match ($this) {
            self::DependsOn => __('This rule makes no sense without the other one.'),
            self::Modifies => __('This rule changes how the other one works.'),
            self::Overrides => __('Where the two disagree, this one wins.'),
            self::ExceptionTo => __('The other rule applies, except in this case.'),
            self::RelatedTo => __('Worth reading together. Neither takes precedence.'),
        };
    }
}
