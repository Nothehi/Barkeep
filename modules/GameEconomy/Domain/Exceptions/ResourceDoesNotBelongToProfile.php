<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a flow, cost, reward or variable names a resource from another
 * profile.
 *
 * The invariant this module is most careful about after game-version ownership,
 * and the one the database cannot express: `action_costs.resource_type_id` has a
 * foreign key to `resource_types`, but nothing in the schema says the resource
 * has to belong to the *same profile* as the action.
 *
 * So it is proved in the application layer, once, by resolving the resource
 * through the profile rather than by looking it up and comparing. A resource
 * from a different configuration is not rejected — it is never found.
 */
final class ResourceDoesNotBelongToProfile extends EconomyRuleViolation
{
    private function __construct(
        public readonly string $profileId,
        public readonly string $resourceId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $profileId, string $resourceId): self
    {
        return new self($profileId, $resourceId, __('That resource belongs to a different balance profile.'));
    }

    public function field(): string
    {
        return 'resource_type_id';
    }
}
