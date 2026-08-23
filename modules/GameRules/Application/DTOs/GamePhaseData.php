<?php

namespace Modules\GameRules\Application\DTOs;

use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleStatus;

/**
 * The validated input required to add or edit a stage of play.
 *
 * Transitions are absent. A phase and the edges out of it are separate records,
 * and a create form that asked for both would demand that the destination phase
 * already existed — which is the wrong way round for the first phase somebody
 * writes.
 */
final readonly class GamePhaseData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $parentPhaseId = null,
        public ?GamePhaseType $phaseType = null,
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
            parentPhaseId: RuleInput::identifier($input, 'parent_phase_id'),
            phaseType: RuleInput::optionalEnum($input, 'phase_type', GamePhaseType::class),
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
