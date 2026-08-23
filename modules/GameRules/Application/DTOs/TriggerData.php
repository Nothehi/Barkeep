<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\TriggerType;

/**
 * The validated input required to name something that happens automatically.
 *
 * Note what is not here: anything the trigger would *do*. A trigger records when,
 * and what points at it says what — see section 23 of the brief on why this module
 * has no field that an execution loop could read.
 */
final readonly class TriggerData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?TriggerType $triggerType = null,
        public ?int $position = null,
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
            triggerType: RuleInput::optionalEnum($input, 'trigger_type', TriggerType::class),
            position: RuleInput::has($input, 'position') ? RuleInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
