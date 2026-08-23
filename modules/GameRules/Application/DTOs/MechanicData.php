<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\MechanicCategory;

/**
 * The validated input required to name a mechanism a rule set uses.
 */
final readonly class MechanicData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?MechanicCategory $category = null,
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
            category: RuleInput::optionalEnum($input, 'category', MechanicCategory::class),
            position: RuleInput::has($input, 'position') ? RuleInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
