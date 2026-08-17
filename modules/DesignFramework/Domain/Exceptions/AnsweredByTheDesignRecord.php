<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when somebody grades or ticks something the design record answers.
 *
 * The whole point of a fact-backed criterion is that nobody assesses it. "Are the
 * player count and playing time decided?" has one honest answer and it is a
 * lookup — accepting a grade for it would put a second, disagreeing answer beside
 * the first, and the interface would then have to choose which to believe.
 *
 * A refusal rather than a silent no-op, because a caller sending a grade has
 * misunderstood something and should be told where the answer actually comes
 * from. The screens never offer the control, so reaching this means arriving from
 * the API or from a stale page.
 */
final class AnsweredByTheDesignRecord extends FrameworkRuleViolation
{
    private function __construct(public readonly string $fact, string $message)
    {
        parent::__construct($message);
    }

    /**
     * Raised when a criterion answered by a fact is sent a grade.
     */
    public static function forCriterion(string $fact): self
    {
        return new self($fact, __(
            'This criterion is answered by :fact in the game\'s design rather than by a grade.',
            ['fact' => $fact],
        ));
    }

    /**
     * Raised when a checklist item answered by a fact is ticked by hand.
     */
    public static function forChecklistItem(string $fact): self
    {
        return new self($fact, __(
            'This is met by recording :fact in the game\'s design rather than by ticking it.',
            ['fact' => $fact],
        ));
    }

    public function status(): int
    {
        return 409;
    }
}
