<?php

namespace Modules\Identity\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\Identity\Domain\Exceptions\InvalidEmailAddress;
use Stringable;

/**
 * A normalised email address.
 *
 * Identity is the only context that decides what "the same email address"
 * means, so normalisation lives here rather than at each call site.
 */
final readonly class EmailAddress implements Stringable
{
    private function __construct(public string $value) {}

    /**
     * Normalise and validate the given address.
     *
     * @throws InvalidEmailAddress
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::lower(trim($value));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidEmailAddress::forValue($value);
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
