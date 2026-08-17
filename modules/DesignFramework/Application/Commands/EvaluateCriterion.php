<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\EvaluateCriterionData;
use Modules\DesignFramework\Application\Services\FrameworkContentLocator;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Events\CriterionEvaluated;
use Modules\DesignFramework\Domain\Exceptions\AnsweredByTheDesignRecord;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record how a game measures up against one of its framework's criteria.
 *
 * The module's central separation, in one command. The criterion was resolved through the
 * version the game adopted — by the route binding, so a criterion from another version 404s
 * before this runs — and is confirmed again here, so a caller arriving without a request is held
 * to the same rule. The assessment is then written against the game's adoption, never against the
 * criterion, which is what stops every studio following v1 from sharing one answer.
 *
 * ## Re-assessment overwrites
 *
 * A criterion asks how the design is *now*, so evaluating it again replaces the standing
 * answer. A history of grades over time is a genuinely useful feature and would need its
 * own table; relaxing the unique index to get it for free would instead produce a pile of
 * rows with no defined "current" one.
 *
 * The previous grade travels on the event, because movement is the interesting fact. "Weak
 * became good" is what a progress narrative is built from; "is good" is not.
 *
 * The evaluator is the signed in account rather than anything the caller sent.
 */
final class EvaluateCriterion
{
    public function __construct(
        private readonly GameFrameworkGuard $guard,
        private readonly FrameworkContentLocator $content,
        private readonly GameFrameworkRepository $adoptions,
    ) {}

    public function handle(
        User $evaluator,
        GameFramework $adoption,
        DesignCriterion $criterion,
        EvaluateCriterionData $data,
    ): CriterionEvaluation {
        $this->guard->ensureAdoptionAcceptsProgress($adoption);
        $this->content->ensureAdopted($adoption, $criterion);

        /*
         * A criterion answered by the game's design record is not graded. The
         * screens never offer the buttons, so reaching this means arriving from
         * the API or from a page that loaded before the framework author
         * attached the fact — and storing a grade would leave a second answer
         * disagreeing with the record.
         */
        if ($criterion->isAnsweredByTheDesignRecord()) {
            throw AnsweredByTheDesignRecord::forCriterion((string) $criterion->satisfied_by);
        }

        $existing = $this->adoptions->findEvaluation($adoption, $criterion);
        $previous = $existing?->status;

        $evaluatedAt = now()->toImmutable();

        $evaluation = $existing ?? new CriterionEvaluation;

        $evaluation->fill(['notes' => $data->notes]);

        $evaluation->game_framework_id = $adoption->getKey();
        $evaluation->criterion_id = $criterion->getKey();
        $evaluation->status = $data->rating;
        $evaluation->evaluated_by = $evaluator->id;
        $evaluation->evaluated_at = $evaluatedAt;

        $evaluation->save();

        $evaluation->setRelation('gameFramework', $adoption);
        $evaluation->setRelation('criterion', $criterion);
        $evaluation->setRelation('evaluator', $evaluator);

        event(new CriterionEvaluated(
            evaluationId: $evaluation->id,
            gameFrameworkId: $adoption->getKey(),
            gameId: $adoption->game_id,
            criterionId: $criterion->getKey(),
            rating: $data->rating,
            previousRating: $previous,
            evaluatedBy: $evaluator->id,
            evaluatedAt: $evaluatedAt->toDateTimeImmutable(),
        ));

        return $evaluation;
    }
}
