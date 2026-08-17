<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;
use Modules\DesignFramework\Domain\Enums\CriterionRating;

/**
 * Dispatched when a designer grades their game against a criterion.
 *
 * Fires on re-evaluation as well as on the first assessment, and carries the
 * previous rating so a consumer can tell the two apart — and, more usefully, can
 * see movement. "Weak became good" is the event a progress narrative is built
 * from; "is good" is not.
 *
 * The rating travels as the enum rather than as a number. There is no numeric
 * score in this module on purpose, and handing a consumer one would invite it to
 * invent the weighting the domain has declined to.
 */
final readonly class CriterionEvaluated
{
    public function __construct(
        public string $evaluationId,
        public string $gameFrameworkId,
        public string $gameId,
        public string $criterionId,
        public CriterionRating $rating,
        public ?CriterionRating $previousRating,
        public string $evaluatedBy,
        public DateTimeImmutable $evaluatedAt,
    ) {}
}
