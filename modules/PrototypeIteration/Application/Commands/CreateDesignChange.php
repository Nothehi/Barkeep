<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DesignChangeData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Events\DesignChangeCreated;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * Record something the designer deliberately changed.
 *
 * The smallest write in the module and the one performed most often — a designer coming
 * back from a session types three or four of these — so it is kept to exactly what
 * cannot be reconstructed later: what changed, and why.
 *
 * The reason is required, and this is the command where that requirement earns its keep.
 * A studio in a hurry will happily record "reduced starting resources to 3" and move on;
 * six months later that entry answers nothing, because the number is visible in the
 * rules and the argument is not. Insisting on the reason is the difference between a
 * changelog and a design rationale.
 *
 * The change is attached to the iteration from a resolved route binding, so a caller
 * cannot record a change against a cycle they merely named — and the guard refuses a
 * cycle that has closed, because a change recorded against finished work is one nobody
 * can date.
 */
final class CreateDesignChange
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $creator, Iteration $iteration, DesignChangeData $data): DesignChange
    {
        $this->guard->ensureIterationAcceptsWork($iteration);

        $change = new DesignChange;

        $change->fill([
            'title' => $data->title,
            'description' => $data->description,
            'reason' => $data->reason,
        ]);

        $change->iteration_id = $iteration->getKey();
        $change->category = $data->category;
        $change->created_by = $creator->id;

        $change->save();

        $change->setRelation('iteration', $iteration);
        $change->setRelation('creator', $creator);

        event(new DesignChangeCreated(
            changeId: $change->id,
            iterationId: $iteration->getKey(),
            gameId: $iteration->game_id,
            category: $change->category,
            createdBy: $creator->id,
        ));

        return $change;
    }
}
