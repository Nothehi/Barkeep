<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;

/**
 * Disconnect an iteration from a playtest it was not actually tested through.
 *
 * Deletes the association and nothing else. The playtest is untouched — it belongs to
 * Playtesting, it is evidence in its own right, and this module has no business removing
 * it.
 *
 * Allowed only while the cycle is open, like every other write to an iteration. Once it
 * closes, which playtests it was judged on is part of the record: an iteration whose
 * evidence could be removed afterwards would let a conclusion be left standing while the
 * observations that contradicted it quietly disappeared.
 *
 * No event is dispatched, and the asymmetry with attachment is deliberate. Attaching
 * records that a cycle was tested through a playtest, which is a fact about the design
 * work; detaching corrects a bookkeeping mistake, and an event announcing it would invite
 * consumers to treat "somebody picked the wrong playtest from a dropdown" as design
 * activity.
 */
final class DetachPlaytestFromIteration
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, IterationPlaytest $link): void
    {
        $iteration = $link->iteration;

        if ($iteration !== null) {
            $this->guard->ensureIterationAcceptsWork($iteration);
        }

        $link->delete();
    }
}
