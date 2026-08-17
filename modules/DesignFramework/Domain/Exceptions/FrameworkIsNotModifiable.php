<?php

namespace Modules\DesignFramework\Domain\Exceptions;

use Modules\DesignFramework\Domain\Enums\FrameworkStatus;

/**
 * Raised when something tries to change a framework that has stopped changing.
 *
 * A framework's own record — its name, address and description — freezes when it
 * is published, because those are what games cite. Its versions are a separate
 * question: a published framework happily gains a new draft version, which is
 * the whole mechanism by which a methodology evolves.
 */
final class FrameworkIsNotModifiable extends FrameworkRuleViolation
{
    private function __construct(public readonly FrameworkStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(FrameworkStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    public function status(): int
    {
        return 409;
    }
}
