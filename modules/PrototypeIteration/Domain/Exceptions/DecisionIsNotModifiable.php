<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;

/**
 * Raised when a settled decision's wording is edited.
 *
 * A decision stays open to rewording while it is still being argued about, and
 * freezes the moment it is agreed or refused. Editing the text of an accepted
 * decision changes what the design history says the studio decided, which is
 * precisely the record it exists to keep.
 */
final class DecisionIsNotModifiable extends IterationRuleViolation
{
    private function __construct(public readonly DecisionStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(DecisionStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the iteration around the decision has closed.
     */
    public static function becauseIterationIsClosed(string $reason): self
    {
        return new self(DecisionStatus::Proposed, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
