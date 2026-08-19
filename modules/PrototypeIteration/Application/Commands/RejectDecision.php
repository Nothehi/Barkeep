<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * Decide against a proposed conclusion.
 *
 * Kept as a real, recorded ending rather than letting an unwanted proposal be deleted.
 * "We considered removing the trading phase and decided not to, because it is where the
 * table talk happens" is frequently more useful eighteen months later than the reasons
 * behind the things the studio did do — and it is exactly the argument somebody will make
 * again if the record does not show it was already had.
 *
 * Terminal, for the same reason acceptance is: see {@see AcceptDecision}.
 */
final class RejectDecision
{
    public function __construct(private readonly SettleDecision $settle) {}

    public function handle(User $actor, DesignDecision $decision): DesignDecision
    {
        return $this->settle->handle($actor, $decision, DecisionStatus::Rejected);
    }
}
