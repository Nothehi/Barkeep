<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;

/**
 * The validated input required to write down why a number is what it is.
 */
final readonly class BalanceAssumptionData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?AssumptionCategory $category = null,
        public ?AssumptionConfidence $confidence = null,
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
        $confidence = EconomyInput::identifier($input, 'confidence');

        return new self(
            title: EconomyInput::text($input, 'title'),
            description: EconomyInput::text($input, 'description'),
            category: $category === null ? null : AssumptionCategory::tryFrom($category),
            confidence: $confidence === null ? null : AssumptionConfidence::tryFrom($confidence),
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
