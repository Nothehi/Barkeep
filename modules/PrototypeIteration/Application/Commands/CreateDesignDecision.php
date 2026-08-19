<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DesignDecisionData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Events\DecisionCreated;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * Propose a conclusion.
 *
 * A decision starts proposed and unattributed, and both halves of that matter. Nobody is
 * recorded as having settled it, because nobody has — a `decided_by` filled in at creation
 * would make every proposal read as agreed, which is precisely the distinction the
 * lifecycle exists to keep.
 *
 * Proposing and accepting are separate acts for the same reason. The gap between them is
 * where the argument happens, and a studio where decisions sit for a week before being
 * agreed is working differently from one where they are agreed on sight. Collapsing the
 * two would lose that, and would also lose the record of proposals that were talked out
 * of — which is often the more useful history.
 *
 * All three fields are required. A decision is the sentence somebody will read in a year
 * to find out why the game is the way it is, and the reason is the half that lets them
 * re-examine it when the situation changes rather than merely obey it.
 */
final class CreateDesignDecision
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $creator, Iteration $iteration, DesignDecisionData $data): DesignDecision
    {
        $this->guard->ensureIterationAcceptsWork($iteration);

        $decision = new DesignDecision;

        $decision->fill([
            'title' => $data->title,
            'decision' => $data->decision,
            'reason' => $data->reason,
        ]);

        $decision->iteration_id = $iteration->getKey();
        $decision->status = DecisionStatus::default();
        $decision->created_by = $creator->id;

        $decision->save();

        $decision->setRelation('iteration', $iteration);
        $decision->setRelation('creator', $creator);

        event(new DecisionCreated(
            decisionId: $decision->id,
            iterationId: $iteration->getKey(),
            gameId: $iteration->game_id,
            createdBy: $creator->id,
        ));

        return $decision;
    }
}
