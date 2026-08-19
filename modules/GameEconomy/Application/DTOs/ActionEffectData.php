<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The validated input required to record what an action does beyond resources.
 *
 * The target is free text and carries no reference to anything, which is the
 * whole reason effects exist as their own record: "Building II" is not a
 * resource, and making the designer model it as one to satisfy a foreign key
 * would be the schema deciding what their game contains.
 */
final readonly class ActionEffectData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?ActionEffectType $effectType = null,
        public ?string $target = null,
        public ?Quantity $value = null,
        public ?string $description = null,
        public array $sentFields = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $type = EconomyInput::identifier($input, 'effect_type');

        return new self(
            effectType: $type === null ? null : ActionEffectType::tryFrom($type),
            target: EconomyInput::text($input, 'target'),
            value: EconomyInput::amount($input, 'value'),
            description: EconomyInput::text($input, 'description'),
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
