<?php

namespace Modules\Workspace\Domain\ValueObjects;

use Illuminate\Support\Str;
use SensitiveParameter;
use Stringable;

/**
 * The secret that lets somebody accept a workspace invitation.
 *
 * The plaintext exists only long enough to be put in an email; the database
 * stores its digest. Anyone who reads a backup, a log or an API response
 * therefore learns nothing they could redeem.
 *
 * A plain SHA-256 digest is enough here — unlike a password, the token is
 * 40 characters of CSPRNG output, so there is nothing to brute force.
 */
final readonly class InvitationToken implements Stringable
{
    public const LENGTH = 40;

    private function __construct(#[SensitiveParameter] public string $plainText) {}

    /**
     * Mint a fresh token.
     */
    public static function generate(): self
    {
        return new self(Str::random(self::LENGTH));
    }

    /**
     * Wrap a token supplied by a caller, without trusting it.
     */
    public static function fromString(#[SensitiveParameter] string $value): self
    {
        return new self($value);
    }

    /**
     * The digest stored alongside the invitation.
     */
    public function hash(): string
    {
        return hash('sha256', $this->plainText);
    }

    /**
     * Compare against a stored digest in constant time.
     */
    public function matches(string $hash): bool
    {
        return hash_equals($hash, $this->hash());
    }

    public function __toString(): string
    {
        return $this->plainText;
    }

    /**
     * Keep the plaintext out of dumps, logs and stack traces.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['plainText' => '[redacted]'];
    }
}
