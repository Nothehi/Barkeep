<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The validated input required to declare or retune a resource.
 *
 * The four flags carry their own defaults rather than being nullable, because
 * they describe the ordinary case — a material you gather, hold and spend — and
 * a designer adding "Wood" should not have to answer four questions to do it.
 *
 * The three bounds are nullable and stay nullable. Null means unbounded, which
 * is a different statement from zero, and a DTO that defaulted them would invent
 * a limit nobody set.
 */
final readonly class ResourceTypeData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $unit = null,
        public ?ResourceCategory $category = null,
        public ?bool $isTradeable = null,
        public ?bool $isAccumulative = null,
        public ?bool $isSpendable = null,
        public ?bool $isConvertible = null,
        public ?Quantity $minValue = null,
        public ?Quantity $maxValue = null,
        public ?Quantity $startingValue = null,
        public ?int $position = null,
        public array $sentFields = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * Every nullable field records whether it was sent, because clearing a
     * maximum and leaving it alone are different edits and both have to be
     * possible from a partial update.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $category = EconomyInput::identifier($input, 'category');

        return new self(
            name: EconomyInput::text($input, 'name'),
            description: EconomyInput::text($input, 'description'),
            unit: EconomyInput::text($input, 'unit'),
            category: $category === null ? null : ResourceCategory::tryFrom($category),
            isTradeable: EconomyInput::has($input, 'is_tradeable') ? EconomyInput::flag($input, 'is_tradeable', true) : null,
            isAccumulative: EconomyInput::has($input, 'is_accumulative') ? EconomyInput::flag($input, 'is_accumulative', true) : null,
            isSpendable: EconomyInput::has($input, 'is_spendable') ? EconomyInput::flag($input, 'is_spendable', true) : null,
            isConvertible: EconomyInput::has($input, 'is_convertible') ? EconomyInput::flag($input, 'is_convertible', false) : null,
            minValue: EconomyInput::amount($input, 'min_value'),
            maxValue: EconomyInput::amount($input, 'max_value'),
            startingValue: EconomyInput::amount($input, 'starting_value'),
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
