<?php

namespace Modules\GameRules\Application\DTOs;

/**
 * The validated input required to say how play moves between two phases.
 *
 * All four identifiers arrive in the request body rather than as route segments,
 * because a transition has no natural place in a URL hierarchy — it belongs to
 * the rule set rather than to either of its ends. Every one of them is therefore
 * resolved *through* the rule set before anything is written, which is exactly
 * what `RuleCatalogue` exists for.
 */
final readonly class PhaseTransitionData
{
    /**
     * @param  list<string>  $sentFields
     */
    public function __construct(
        public ?string $fromPhaseId = null,
        public ?string $toPhaseId = null,
        public ?string $conditionId = null,
        public ?string $triggerId = null,
        public ?int $position = null,
        public array $sentFields = [],
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            fromPhaseId: RuleInput::identifier($input, 'from_phase_id'),
            toPhaseId: RuleInput::identifier($input, 'to_phase_id'),
            conditionId: RuleInput::identifier($input, 'condition_id'),
            triggerId: RuleInput::identifier($input, 'trigger_id'),
            position: RuleInput::has($input, 'position') ? RuleInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
