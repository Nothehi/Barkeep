<?php

namespace Modules\Identity\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidEmailAddress extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('[%s] is not a valid email address.', $value));
    }
}
