<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Events\PlaytestAttachedToIteration;
use Modules\PrototypeIteration\Domain\Exceptions\PlaytestDoesNotBelongToGame;
use Modules\PrototypeIteration\Domain\Exceptions\PlaytestIsAlreadyAttached;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * Connect an iteration to the playtest that tested it.
 *
 * The seam between this module and Playtesting, and the only write that crosses it. What
 * gets stored is an association and nothing else: no title, no session count, no
 * denormalised observation total. Everything an iteration screen shows about an attached
 * playtest is read back through Playtesting's own contract at render time, which is what
 * keeps those numbers equal to the numbers on the playtest's own screen instead of
 * quietly disagreeing with them from the first added session onwards.
 *
 * The playtest id arrives in a request body — there is deliberately no route segment for
 * it, so that this module never binds a Playtesting model — and it is proved to belong to
 * the iteration's game through the adapter before anything is written. A playtest from
 * another studio's project does not resolve rather than being compared and rejected.
 *
 * ## Two guards for the duplicate
 *
 * The check and the unique index are both here on purpose. The check gives a caller a
 * worded refusal against the field they submitted; the index is the authority, and it
 * catches the double-submitted form that slipped between the two. Catching the constraint
 * violation and reporting it as the same rule means the two paths are indistinguishable
 * from outside, which is what stops the race from producing a 500.
 */
final class AttachPlaytestToIteration
{
    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly PlaytestEvidence $playtesting,
        private readonly IterationRepository $iterations,
    ) {}

    /**
     * @throws PlaytestDoesNotBelongToGame when the playtest is not this game's
     * @throws PlaytestIsAlreadyAttached when the pair is already recorded
     */
    public function handle(User $actor, Iteration $iteration, string $playtestId): IterationPlaytest
    {
        $this->guard->ensureIterationAcceptsWork($iteration);

        $game = $iteration->game;

        if ($game === null) {
            throw PlaytestDoesNotBelongToGame::forPair($iteration->game_id, $playtestId);
        }

        $resolved = $this->playtesting->requirePlaytestOf($game, $playtestId);

        if ($this->iterations->hasPlaytest($iteration, $resolved)) {
            throw PlaytestIsAlreadyAttached::forPair($iteration->getKey(), $resolved);
        }

        $link = new IterationPlaytest;

        $link->iteration_id = $iteration->getKey();
        $link->playtest_id = $resolved;
        $link->created_by = $actor->id;

        try {
            $link->save();
        } catch (UniqueConstraintViolationException) {
            /*
             * Somebody attached the same playtest in the window between the check above
             * and this insert. Nothing is wrong with the request that was not already
             * true, so report the rule rather than the constraint.
             */
            throw PlaytestIsAlreadyAttached::forPair($iteration->getKey(), $resolved);
        }

        $link->setRelation('iteration', $iteration);
        $link->setRelation('creator', $actor);

        event(new PlaytestAttachedToIteration(
            linkId: $link->id,
            iterationId: $iteration->getKey(),
            playtestId: $resolved,
            gameId: $game->getKey(),
            attachedBy: $actor->id,
        ));

        return $link;
    }
}
