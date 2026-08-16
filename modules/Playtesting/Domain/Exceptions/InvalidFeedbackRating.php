<?php

namespace Modules\Playtesting\Domain\Exceptions;

use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;

/**
 * Raised when a rating is not on the scale.
 *
 * Validation catches this on every HTTP route before a command runs, so this
 * is the backstop for callers that arrive another way — and the reason the
 * value object can be trusted wherever it appears.
 */
final class InvalidFeedbackRating extends PlaytestRuleViolation
{
    private function __construct(public readonly int $given, string $message)
    {
        parent::__construct($message);
    }

    public static function outOfRange(int $given): self
    {
        return new self($given, __('A rating must be between :min and :max.', [
            'min' => FeedbackRating::MIN,
            'max' => FeedbackRating::MAX,
        ]));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'rating';
    }
}
