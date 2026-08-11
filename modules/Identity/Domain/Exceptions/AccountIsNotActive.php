<?php

namespace Modules\Identity\Domain\Exceptions;

use Modules\Identity\Domain\Enums\UserStatus;
use RuntimeException;

/**
 * Raised when an account that is not active attempts to authenticate or to
 * continue an existing session.
 */
final class AccountIsNotActive extends RuntimeException
{
    private function __construct(public readonly UserStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(UserStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }
}
