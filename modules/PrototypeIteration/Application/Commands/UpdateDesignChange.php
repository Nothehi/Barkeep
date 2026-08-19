<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DesignChangeData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\DesignChange;

/**
 * Reword a change while the cycle it belongs to is still open.
 *
 * A whole-record replacement rather than a partial update, because a change is four
 * short fields and the machinery to express "leave the description alone" would be
 * larger than the thing it operated on.
 *
 * The window is the cycle's, not the change's. A change has no lifecycle of its own —
 * it either happened or it did not — so what freezes it is the iteration closing around
 * it. That is section 53: a completed cycle's changes are part of the design history,
 * and a history whose entries can be reworded afterwards is not one anybody can reason
 * from.
 *
 * No event is dispatched. `DesignChangeCreated` announced that the change happened;
 * fixing its wording an hour later did not change the design.
 */
final class UpdateDesignChange
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignChange $change, DesignChangeData $data): DesignChange
    {
        $iteration = $change->iteration;

        if ($iteration !== null) {
            $this->guard->ensureIterationAcceptsWork($iteration);
        }

        $change->fill([
            'title' => $data->title,
            'description' => $data->description,
            'reason' => $data->reason,
        ]);

        $change->category = $data->category;

        $change->save();

        return $change;
    }
}
