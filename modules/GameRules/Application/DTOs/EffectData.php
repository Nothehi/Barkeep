<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\EffectType;

/**
 * The validated input required to record what a rule or an action does.
 *
 * The value is a string throughout. "+3", "-1", "half, rounded down" and "all of
 * them" are all things a rulebook says, and a numeric field would refuse three of
 * the four while implying that something adds them up. Nothing does: this module
 * describes effects and never carries one out.
 */
final readonly class EffectData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $ruleId = null,
        public ?string $actionId = null,
        public ?EffectType $effectType = null,
        public ?string $target = null,
        public ?string $value = null,
        public ?string $description = null,
        public ?string $economyResourceSlug = null,
        public ?int $position = null,
        public array $sentFields = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            ruleId: RuleInput::identifier($input, 'rule_id'),
            actionId: RuleInput::identifier($input, 'action_id'),
            effectType: RuleInput::optionalEnum($input, 'effect_type', EffectType::class),
            target: RuleInput::text($input, 'target'),
            value: RuleInput::text($input, 'value'),
            description: RuleInput::text($input, 'description'),
            economyResourceSlug: RuleInput::identifier($input, 'economy_resource_slug'),
            position: RuleInput::has($input, 'position') ? RuleInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
