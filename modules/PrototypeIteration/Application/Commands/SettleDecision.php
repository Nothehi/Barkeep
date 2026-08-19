<?php

namespace Modules\PrototypeIteration\Application\Commands;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Events\DecisionAccepted;
use Modules\PrototypeIteration\Domain\Events\DecisionDeferred;
use Modules\PrototypeIteration\Domain\Events\DecisionRejected;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidDecisionTransition;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * Move a decision to a settled state, whichever one.
 *
 * The three public commands beside this file — `AcceptDecision`, `RejectDecision`,
 * `DeferDecision` — are the module's vocabulary, because "accept this decision" is what a
 * designer does and a generic `changeDecisionStatus($status)` would put the choice of
 * which move to make in a request body. That is the mistake this module avoids everywhere
 * it has a lifecycle: a status is never an editable field.
 *
 * What the three share is everything except the target status and the event, and that
 * shared part is not trivial — a row lock, a matrix check against the status read inside
 * it, attribution, a timestamp, and carrying the written row back onto the caller's
 * instance. Three copies of it would be three places for the lock to be forgotten, so it
 * lives here once and the three commands are the names.
 *
 * The lock matters more here than anywhere else in the module. Accepted and rejected are
 * both terminal, so two people pressing "Accept" and "Reject" at the same moment are
 * racing to write the studio's recorded intention. Deciding inside the lock, against the
 * status read inside it, means one of them wins and the other is told plainly that the
 * decision was already settled — rather than a last-write-wins result where the losing
 * click silently rewrites what the studio agreed.
 */
final class SettleDecision
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    /**
     * Move the decision to the given settled status, or refuse.
     */
    public function handle(User $actor, DesignDecision $decision, DecisionStatus $target): DesignDecision
    {
        $this->guard->ensureDecisionIsOpen($decision);

        $decidedAt = now()->toImmutable();

        DB::transaction(function () use ($decision, $target, $actor, $decidedAt): void {
            $fresh = DesignDecision::query()
                ->whereKey($decision->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo($target)) {
                throw InvalidDecisionTransition::between($fresh->status, $target);
            }

            $fresh->forceFill([
                'status' => $target,
                'decided_by' => $actor->id,
                'decided_at' => $decidedAt,
            ]);

            $fresh->save();

            /*
             * Carry the saved row back onto the instance the caller holds, so what gets
             * rendered afterwards is the state that was written rather than the one that
             * was read before the lock.
             */
            $decision->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        $this->announce($decision, $target, $actor, $decidedAt->toDateTimeImmutable());

        return $decision;
    }

    /**
     * Dispatch the event that matches the move.
     *
     * Three events rather than one carrying a status, because a consumer interested in
     * agreed decisions should not have to filter a stream of settlements — and because
     * the accepted and rejected events carry an evidence count that the deferred one has
     * no use for.
     *
     * The count is read after the write, so it describes the decision as it was settled.
     * "Agreed, citing four playtests" and "agreed, citing nothing" are different events to
     * anything reasoning about how a studio decides, and counting the citations would mean
     * a consumer reaching into this module's tables.
     */
    private function announce(
        DesignDecision $decision,
        DecisionStatus $target,
        User $actor,
        DateTimeImmutable $decidedAt,
    ): void {
        $iterationId = $decision->iteration_id;
        $gameId = $decision->gameId();

        match ($target) {
            DecisionStatus::Accepted => event(new DecisionAccepted(
                decisionId: $decision->id,
                iterationId: $iterationId,
                gameId: $gameId,
                evidenceCount: $decision->evidence()->count(),
                decidedBy: $actor->id,
                decidedAt: $decidedAt,
            )),
            DecisionStatus::Rejected => event(new DecisionRejected(
                decisionId: $decision->id,
                iterationId: $iterationId,
                gameId: $gameId,
                evidenceCount: $decision->evidence()->count(),
                decidedBy: $actor->id,
                decidedAt: $decidedAt,
            )),
            DecisionStatus::Deferred => event(new DecisionDeferred(
                decisionId: $decision->id,
                iterationId: $iterationId,
                gameId: $gameId,
                decidedBy: $actor->id,
                decidedAt: $decidedAt,
            )),

            /*
             * Proposed is not reachable: the lifecycle matrix has no edge back to it, so
             * the transition check above has already refused. Listed rather than defaulted
             * so that adding a status to the enum breaks here, where the decision about
             * how to announce it belongs, instead of silently dispatching nothing.
             */
            DecisionStatus::Proposed => null,
        };
    }
}
