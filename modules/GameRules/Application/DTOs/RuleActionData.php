<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleStatus;

/**
 * The validated input required to declare or edit something a player may do.
 *
 * `economyActionSlug` is a handle and never an amount. What the action costs
 * belongs to GameEconomy, and pointing at it rather than copying it is what stops
 * the rules screen and the balance screen from ever disagreeing — see section 16
 * of the module brief and `EconomyDirectory`.
 */
final readonly class RuleActionData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $phaseId = null,
        public ?RuleActionType $actionType = null,
        public ?RuleStatus $status = null,
        public ?string $economyActionSlug = null,
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
            phaseId: RuleInput::identifier($input, 'phase_id'),
            actionType: RuleInput::optionalEnum($input, 'action_type', RuleActionType::class),
            status: RuleInput::optionalEnum($input, 'status', RuleStatus::class),
            economyActionSlug: RuleInput::identifier($input, 'economy_action_slug'),
            position: RuleInput::has($input, 'position') ? RuleInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
