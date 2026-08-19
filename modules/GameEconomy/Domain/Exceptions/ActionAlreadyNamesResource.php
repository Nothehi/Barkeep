<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when an action is given a second cost or reward line for a resource it
 * already names.
 *
 * A build that costs "2 wood and 3 more wood" is a data entry mistake rather
 * than a design — it should be one line reading 5 — so the unique index refuses
 * it. This turns that refusal into something a designer can act on, and points
 * them at the edit they actually meant.
 */
final class ActionAlreadyNamesResource extends EconomyRuleViolation
{
    private function __construct(public readonly string $resourceId, string $message)
    {
        parent::__construct($message);
    }

    public static function asCost(string $resourceId, string $resourceName): self
    {
        return new self($resourceId, __('This action already costs :resource. Edit that line instead.', [
            'resource' => $resourceName,
        ]));
    }

    public static function asReward(string $resourceId, string $resourceName): self
    {
        return new self($resourceId, __('This action already pays out :resource. Edit that line instead.', [
            'resource' => $resourceName,
        ]));
    }

    public function status(): int
    {
        return 409;
    }

    public function field(): string
    {
        return 'resource_type_id';
    }
}
