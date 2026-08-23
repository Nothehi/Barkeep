<?php

namespace Modules\GameRules\Application\DTOs;

/**
 * The validated input required to record a victory, defeat or end condition.
 *
 * One DTO for the three, unlike the models, and the asymmetry is on purpose. The
 * three *records* stay separate because winning, losing and stopping are three
 * different questions a game answers at once — but the fields a form collects for
 * them are identical, and three copies of this class would be three places for a
 * length limit to drift.
 */
final readonly class OutcomeData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $conditionId = null,
        public ?int $priority = null,
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
            conditionId: RuleInput::identifier($input, 'condition_id'),
            priority: RuleInput::has($input, 'priority') ? RuleInput::integer($input, 'priority') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
