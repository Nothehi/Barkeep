<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\RequirementType;

/**
 * The validated input required to gate a rule or an action.
 *
 * Exactly one of `ruleId` and `actionId` is meant to be set. The DTO carries both
 * because the request can send either, and the command refuses the two mistakes —
 * both, or neither — rather than guessing which was meant.
 */
final readonly class RequirementData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $ruleId = null,
        public ?string $actionId = null,
        public ?RequirementType $requirementType = null,
        public ?string $description = null,
        public ?string $value = null,
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
            requirementType: RuleInput::optionalEnum($input, 'requirement_type', RequirementType::class),
            description: RuleInput::text($input, 'description'),
            value: RuleInput::text($input, 'value'),
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
