<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\Enums\CriterionRating;

/**
 * The validated input for grading a game against one criterion.
 *
 * The rating is required and cannot be "not evaluated": that is the state a
 * criterion is in before anybody acts, and accepting it here would make clearing
 * an assessment look like making one.
 *
 * The notes are optional and are the most valuable part of the record. A bare
 * "needs work" six months later tells nobody what needed work.
 */
final readonly class EvaluateCriterionData
{
    public function __construct(
        public CriterionRating $rating,
        public ?string $notes = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            rating: CriterionRating::from((string) $input['status']),
            notes: FrameworkInput::text($input, 'notes'),
        );
    }
}
