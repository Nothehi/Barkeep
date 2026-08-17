<?php

namespace Modules\DesignFramework\Domain\Enums;

/**
 * How well a game currently meets one design criterion.
 *
 * Four grades and an absence, and the absence is the important one. "Not
 * evaluated" is not a bad score; it means nobody has looked yet, and a
 * progress calculation that treated the two the same would tell a designer
 * their untouched game was failing.
 *
 * The scale is deliberately coarse. This is structured self-assessment, and a
 * designer choosing between seven grades spends their attention on the grade
 * rather than on the design. There is no numeric weighting here on purpose —
 * see section 11 and `FrameworkProgressCalculator`: progress counts *whether*
 * a criterion was evaluated, not how highly it scored. Turning "strong" into
 * points would make a designer who graded their game honestly look worse than
 * one who did not, which is precisely backwards.
 */
enum CriterionRating: string
{
    case NotEvaluated = 'not_evaluated';
    case Weak = 'weak';
    case NeedsWork = 'needs_work';
    case Good = 'good';
    case Strong = 'strong';

    /**
     * Where every criterion starts.
     */
    public static function default(): self
    {
        return self::NotEvaluated;
    }

    /**
     * The grades a designer may actually choose.
     *
     * "Not evaluated" is excluded: it is the state a criterion is in before
     * anybody acts, and offering it as an option would make clearing an
     * assessment look like making one. Clearing happens by deleting the
     * evaluation, which the module does not currently expose.
     *
     * @return list<self>
     */
    public static function grades(): array
    {
        return [self::Weak, self::NeedsWork, self::Good, self::Strong];
    }

    /**
     * Determine whether this rating represents a judgement having been made.
     */
    public function isEvaluated(): bool
    {
        return $this !== self::NotEvaluated;
    }

    /**
     * Determine whether the designer is satisfied with this aspect.
     *
     * Not used by progress, and not a gate on anything. It exists so that a
     * phase page can draw attention to what still needs work without each
     * screen deciding for itself where the line falls.
     */
    public function isSatisfactory(): bool
    {
        return $this === self::Good || $this === self::Strong;
    }

    /**
     * A human readable label for the rating.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotEvaluated => __('Not evaluated'),
            self::Weak => __('Weak'),
            self::NeedsWork => __('Needs work'),
            self::Good => __('Good'),
            self::Strong => __('Strong'),
        };
    }

    /**
     * What choosing this grade is claiming.
     *
     * Sent to the client alongside the label, because the difference between
     * "weak" and "needs work" is not self-evident and a designer guessing at it
     * is a designer producing noise.
     */
    public function description(): string
    {
        return match ($this) {
            self::NotEvaluated => __('Nobody has assessed this yet.'),
            self::Weak => __('This does not work, and we know why.'),
            self::NeedsWork => __('This works, but not well enough yet.'),
            self::Good => __('This does its job.'),
            self::Strong => __('This is a reason to play the game.'),
        };
    }
}
