<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;

/**
 * The validated input required to name a reusable logical requirement.
 *
 * Three parts and a name, and nothing that could be evaluated. The value stays a
 * string because the ten operators compare against different things — a number, a
 * name, a list, or nothing at all — and because nothing in this module compares
 * them. The validator checks the *pairing*, which is a different job from
 * checking a type.
 */
final readonly class ConditionData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?ConditionType $conditionType = null,
        public ?ConditionOperator $operator = null,
        public ?string $value = null,
        public array $sentFields = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: RuleInput::text($input, 'name'),
            description: RuleInput::text($input, 'description'),
            conditionType: RuleInput::optionalEnum($input, 'condition_type', ConditionType::class),
            operator: RuleInput::optionalEnum($input, 'operator', ConditionOperator::class),
            value: RuleInput::text($input, 'value'),
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
