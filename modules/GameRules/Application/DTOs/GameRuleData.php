<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;

/**
 * The validated input required to write or edit a rule.
 *
 * Requirements, effects and references are absent: each has its own endpoint,
 * because each is a separate row and editing one must not be able to disturb
 * another. A rule is written first and gated afterwards, which is how designers
 * work.
 *
 * `parentRuleId` and `phaseId` are the two that carry a real invariant. Both are
 * proved to belong to the same rule set before anything is written, and the
 * parent is additionally checked for a cycle — see `RuleCatalogue` and
 * `CycleDetector`.
 */
final readonly class GameRuleData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $parentRuleId = null,
        public ?string $phaseId = null,
        public ?RuleType $ruleType = null,
        public ?RuleStatus $status = null,
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
            parentRuleId: RuleInput::identifier($input, 'parent_rule_id'),
            phaseId: RuleInput::identifier($input, 'phase_id'),
            ruleType: RuleInput::optionalEnum($input, 'rule_type', RuleType::class),
            status: RuleInput::optionalEnum($input, 'status', RuleStatus::class),
            position: RuleInput::has($input, 'position') ? RuleInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
