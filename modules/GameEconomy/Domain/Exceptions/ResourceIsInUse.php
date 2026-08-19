<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a resource anything is priced in is deleted.
 *
 * The database already refuses this — costs and rewards reference resources with
 * `restrictOnDelete` — but a foreign key violation reaches a designer as a 500,
 * and the thing they need to know is not "constraint failed" but "eleven actions
 * are priced in wood".
 *
 * Deleting a resource that actions depend on would silently make every one of
 * them free, which is the most damaging single change that could be made to a
 * balance configuration without anybody noticing. So the refusal says how much
 * is at stake and leaves the designer to unpick it.
 */
final class ResourceIsInUse extends EconomyRuleViolation
{
    private function __construct(
        public readonly string $resourceId,
        public readonly int $usage,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forResource(string $resourceId, int $usage): self
    {
        return new self($resourceId, $usage, trans_choice(
            'This resource is used by :count record and cannot be deleted. Remove that first.|This resource is used by :count records and cannot be deleted. Remove those first.',
            $usage,
            ['count' => $usage],
        ));
    }

    public function status(): int
    {
        return 409;
    }
}
