<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The validated input required to declare or retune a resource flow.
 *
 * The resource id is here because a flow names which resource it moves and there
 * is no route segment for it. It is proved to belong to the same profile as the
 * flow before anything is written — the invariant the database cannot express.
 *
 * The amount is a magnitude, never signed. Direction comes from the flow type,
 * and accepting a negative here would let a stored row contradict itself.
 */
final readonly class ResourceFlowData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $resourceTypeId = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?ResourceFlowType $flowType = null,
        public ?Quantity $amount = null,
        public ?string $condition = null,
        public ?int $position = null,
        public array $sentFields = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $type = EconomyInput::identifier($input, 'flow_type');

        return new self(
            resourceTypeId: EconomyInput::identifier($input, 'resource_type_id'),
            name: EconomyInput::text($input, 'name'),
            description: EconomyInput::text($input, 'description'),
            flowType: $type === null ? null : ResourceFlowType::tryFrom($type),
            amount: EconomyInput::amount($input, 'amount'),
            condition: EconomyInput::text($input, 'condition'),
            position: EconomyInput::has($input, 'position') ? EconomyInput::integer($input, 'position') : null,
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
