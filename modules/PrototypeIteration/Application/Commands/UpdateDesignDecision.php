<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DesignDecisionData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * Reword a decision that is still open.
 *
 * Open means proposed or deferred — a decision still being argued about, whose wording is
 * fair game. The moment it is accepted or rejected this refuses, because editing the text
 * of a settled decision changes what the design history says the studio agreed to, which
 * is the exact record it exists to keep.
 *
 * That is why reversal is not available anywhere in this module: not as an edit here, and
 * not as a transition in the lifecycle. A studio that has changed its mind records a new
 * decision in a later iteration saying so — which is also how anybody reading the history
 * back would want to find out, because "we decided X, then six weeks later decided not-X"
 * is the actual story.
 */
final class UpdateDesignDecision
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignDecision $decision, DesignDecisionData $data): DesignDecision
    {
        $this->guard->ensureDecisionIsModifiable($decision);

        $decision->fill([
            'title' => $data->title,
            'decision' => $data->decision,
            'reason' => $data->reason,
        ]);

        $decision->save();

        return $decision;
    }
}
