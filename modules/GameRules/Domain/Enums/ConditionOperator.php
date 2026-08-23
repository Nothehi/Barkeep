<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Labelled;

/**
 * How a condition's subject is compared to its value.
 *
 * Ten operators, chosen to cover what a board game rule actually says and
 * nothing more. There is no arithmetic, no nesting and no function call, because
 * a condition here is read by people rather than evaluated by anything — see
 * section 18 of the brief.
 *
 * {@see IsTrue} and {@see IsFalse} take no value, which is the one shape the
 * interface has to know about: a condition builder showing an empty value box
 * beside "is true" is asking a question with no answer.
 */
enum ConditionOperator: string implements Labelled
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case In = 'in';
    case NotIn = 'not_in';

    /**
     * The operator a condition uses when nobody chose one.
     */
    public static function default(): self
    {
        return self::Equals;
    }

    /**
     * Determine whether the operator needs a value beside it.
     *
     * The two unary cases say everything themselves. A form that still showed a
     * value field for them would be asking for an answer it would then ignore,
     * and the validator reports a value supplied against one as a finding.
     */
    public function expectsValue(): bool
    {
        return ! in_array($this, [self::IsTrue, self::IsFalse], strict: true);
    }

    /**
     * Determine whether the value is a list rather than a single thing.
     */
    public function expectsList(): bool
    {
        return in_array($this, [self::In, self::NotIn], strict: true);
    }

    /**
     * Determine whether the comparison only makes sense against a number.
     *
     * What lets the validator notice "rounds elapsed is greater than blue",
     * which is a sentence somebody typed by accident rather than a rule.
     */
    public function expectsNumber(): bool
    {
        return in_array($this, [
            self::GreaterThan,
            self::GreaterThanOrEqual,
            self::LessThan,
            self::LessThanOrEqual,
        ], strict: true);
    }

    /**
     * A human readable label for the operator.
     */
    public function label(): string
    {
        return match ($this) {
            self::Equals => __('is'),
            self::NotEquals => __('is not'),
            self::GreaterThan => __('is more than'),
            self::GreaterThanOrEqual => __('is at least'),
            self::LessThan => __('is less than'),
            self::LessThanOrEqual => __('is at most'),
            self::IsTrue => __('is true'),
            self::IsFalse => __('is false'),
            self::In => __('is one of'),
            self::NotIn => __('is none of'),
        };
    }

    /**
     * The mathematical shorthand, for a condition builder that has no room for
     * words.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::Equals => '=',
            self::NotEquals => '≠',
            self::GreaterThan => '>',
            self::GreaterThanOrEqual => '≥',
            self::LessThan => '<',
            self::LessThanOrEqual => '≤',
            self::IsTrue => '✓',
            self::IsFalse => '✗',
            self::In => '∈',
            self::NotIn => '∉',
        };
    }
}
