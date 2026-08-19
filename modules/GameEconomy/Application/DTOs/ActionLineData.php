<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The validated input required to price an action, or to have it pay out.
 *
 * One DTO for costs and rewards, which is the one place in the module the two
 * are treated alike — and they are alike here because the *input* is identical:
 * a resource, an amount, and optionally a range. They diverge in what they mean,
 * which is why they are separate tables, separate commands and separate panels.
 *
 * The resource id is proved to belong to the action's own profile before
 * anything is written.
 */
final readonly class ActionLineData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $resourceTypeId = null,
        public ?Quantity $amount = null,
        public ?bool $isVariable = null,
        public ?Quantity $minAmount = null,
        public ?Quantity $maxAmount = null,
        public array $sentFields = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            resourceTypeId: EconomyInput::identifier($input, 'resource_type_id'),
            amount: EconomyInput::amount($input, 'amount'),
            isVariable: EconomyInput::has($input, 'is_variable') ? EconomyInput::flag($input, 'is_variable') : null,
            minAmount: EconomyInput::amount($input, 'min_amount'),
            maxAmount: EconomyInput::amount($input, 'max_amount'),
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
