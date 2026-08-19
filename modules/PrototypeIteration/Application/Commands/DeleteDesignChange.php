<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\DesignChange;

/**
 * Remove a change from an open cycle.
 *
 * The only record of design work the module lets anybody delete, and the reason it is
 * allowed is narrow: while a cycle is open, a change entered by mistake — the wrong
 * iteration, a duplicate, a line typed into the wrong box — is a bookkeeping error, and
 * leaving it there would make the cycle's own account wrong.
 *
 * Once the cycle closes, this refuses. At that point the change is part of the design
 * history, and a designer who has changed their mind about the change itself records a
 * *new* change reversing it in a later cycle — which is a truer account anyway, because
 * "we removed the trading phase and then put it back" is what actually happened.
 *
 * Experiments and decisions have no delete at all, even while open. A change is a
 * statement of fact about an edit; an experiment carries a result and a decision carries
 * an argument, and removing either would take reasoning out of the record rather than
 * correcting an entry.
 */
final class DeleteDesignChange
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignChange $change): void
    {
        $iteration = $change->iteration;

        if ($iteration !== null) {
            $this->guard->ensureIterationAcceptsWork($iteration);
        }

        $change->delete();
    }
}
