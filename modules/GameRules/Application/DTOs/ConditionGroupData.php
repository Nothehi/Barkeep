<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\LogicOperator;

/**
 * The validated input required to combine several conditions.
 *
 * One operator for the whole group. Section 19 of the brief rules out nesting,
 * and the DTO's shape is where that decision is most visible: there is no field
 * here for a child group, and there is deliberately nowhere to put one.
 */
final readonly class ConditionGroupData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?LogicOperator $logicOperator = null,
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
            logicOperator: RuleInput::optionalEnum($input, 'logic_operator', LogicOperator::class),
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
