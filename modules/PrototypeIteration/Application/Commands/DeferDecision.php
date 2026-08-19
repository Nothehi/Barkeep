<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * Put a decision off rather than settling it.
 *
 * The one non-terminal ending in the decision lifecycle, and it exists because "we will
 * look at this again after the convention" is a real answer that studios give constantly.
 * Without it, a decision nobody is ready to make has to be either forced or left sitting
 * as an untouched proposal, and the second is indistinguishable from one that was
 * forgotten.
 *
 * A deferred decision can be taken up again, so this is the one settlement a consumer may
 * see more than once for the same decision — deferred, revisited, deferred again. That is
 * not a defect in the lifecycle; it is what a hard question looks like over three months.
 */
final class DeferDecision
{
    public function __construct(private readonly SettleDecision $settle) {}

    public function handle(User $actor, DesignDecision $decision): DesignDecision
    {
        return $this->settle->handle($actor, $decision, DecisionStatus::Deferred);
    }
}
