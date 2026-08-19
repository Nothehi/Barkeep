<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * Agree a proposed conclusion.
 *
 * The most consequential single act in the module. An accepted decision is the sentence
 * the design will be built on, and it is terminal — there is no un-accept, because
 * reversing it in place would leave the game carrying a change whose recorded
 * justification now argues against it. A studio that changes its mind records a new
 * decision in a later iteration.
 *
 * A named command rather than a status field, so that "accept" is something a designer
 * does rather than a value a request body sets. The mechanics live in
 * {@see SettleDecision}, which is where the row lock and the attribution are.
 */
final class AcceptDecision
{
    public function __construct(private readonly SettleDecision $settle) {}

    public function handle(User $actor, DesignDecision $decision): DesignDecision
    {
        return $this->settle->handle($actor, $decision, DecisionStatus::Accepted);
    }
}
