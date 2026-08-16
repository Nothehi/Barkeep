<?php

namespace Modules\Playtesting\Domain\Exceptions;

/**
 * Raised when a playtest with nothing in it is asked to complete.
 *
 * The one evidence rule the lifecycle enforces. Completing a playtest asserts
 * that a question was investigated, and an investigation with no sessions did
 * not happen — whatever the designer concluded, they did not conclude it from
 * this.
 *
 * A playtest that turned out not to be worth running is cancelled instead,
 * which records the same outcome honestly.
 *
 * Note what is *not* required: that the sessions were completed. A playtest
 * whose every session was cancelled still taught somebody something — usually
 * that the version was not ready — and the designer is better placed than this
 * rule to judge whether the question has been answered.
 */
final class PlaytestHasNoSessions extends PlaytestRuleViolation
{
    private function __construct(public readonly string $playtestId, string $message)
    {
        parent::__construct($message);
    }

    public static function forPlaytest(string $playtestId): self
    {
        return new self($playtestId, __('A playtest cannot be completed before it has a session.'));
    }

    public function status(): int
    {
        return 409;
    }
}
