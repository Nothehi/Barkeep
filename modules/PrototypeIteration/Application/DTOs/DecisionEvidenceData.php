<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\EvidenceType;

/**
 * The validated input for citing something in support of a decision.
 *
 * A type, an optional reference and an optional description — and the
 * combinations that make sense are enforced by the command rather than by the
 * shape, because the rule depends on the type. A playtest, observation, feedback
 * or experiment citation needs a reference; a note needs a description and takes
 * no reference at all.
 *
 * The description is never a copy of what was cited. "Players spent less time
 * waiting" belongs to the observation in Playtesting; what belongs here is the
 * reason somebody thought that observation supported this decision — which is the
 * part of a citation that would otherwise be lost.
 */
final readonly class DecisionEvidenceData
{
    public function __construct(
        public EvidenceType $type,
        public ?string $referenceId = null,
        public ?string $description = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * The type is resolved with `from` rather than `tryFrom`: a citation whose
     * type named nothing must not silently become a note, because a note is the
     * one type that needs no reference and the reference would then be dropped.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            type: EvidenceType::from((string) $input['type']),
            referenceId: IterationInput::identifier($input, 'reference_id'),
            description: IterationInput::text($input, 'description'),
        );
    }
}
