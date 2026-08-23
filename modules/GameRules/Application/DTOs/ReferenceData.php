<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\ReferenceType;

/**
 * The validated input required to relate one rule to another.
 */
final readonly class ReferenceData
{
    public function __construct(
        public ?string $referencedRuleId = null,
        public ?ReferenceType $referenceType = null,
        public ?string $description = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            referencedRuleId: RuleInput::identifier($input, 'referenced_rule_id'),
            referenceType: RuleInput::optionalEnum($input, 'reference_type', ReferenceType::class),
            description: RuleInput::text($input, 'description'),
        );
    }
}
