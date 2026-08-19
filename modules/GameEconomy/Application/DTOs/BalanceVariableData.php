<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The validated input required to declare or retune a tunable number.
 *
 * The two optional references are the interesting part. A variable may be about
 * a resource, an action, both or neither, and both ids are proved to belong to
 * the variable's own profile before anything is written — the same invariant a
 * cost's resource is held to, asked from a different direction.
 */
final readonly class BalanceVariableData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?Quantity $value = null,
        public ?string $unit = null,
        public ?Quantity $minValue = null,
        public ?Quantity $maxValue = null,
        public ?Quantity $step = null,
        public ?BalanceVariableCategory $category = null,
        public ?string $resourceTypeId = null,
        public ?string $actionId = null,
        public array $sentFields = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $category = EconomyInput::identifier($input, 'category');

        return new self(
            name: EconomyInput::text($input, 'name'),
            description: EconomyInput::text($input, 'description'),
            value: EconomyInput::amount($input, 'value'),
            unit: EconomyInput::text($input, 'unit'),
            minValue: EconomyInput::amount($input, 'min_value'),
            maxValue: EconomyInput::amount($input, 'max_value'),
            step: EconomyInput::amount($input, 'step'),
            category: $category === null ? null : BalanceVariableCategory::tryFrom($category),
            resourceTypeId: EconomyInput::identifier($input, 'resource_type_id'),
            actionId: EconomyInput::identifier($input, 'action_id'),
            sentFields: array_keys($input),
        );
    }

    /**
     * Determine whether the request mentioned a field at all.
     */
    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
