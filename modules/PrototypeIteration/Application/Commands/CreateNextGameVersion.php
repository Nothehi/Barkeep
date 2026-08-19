<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Exceptions\IterationIsNotConcluded;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\GameDesign\GameCatalogue;
use RuntimeException;

/**
 * Cut the next design state of a game, on the strength of what an iteration found.
 *
 * The deliberate seam section 48 asks for, and everything about it is arranged so that
 * ownership stays where it belongs. The version is created *by GameDesign*: numbered by
 * its allocator, guarded by its rules, announced by its event. This command's entire
 * job is to be the button on the iteration screen that a designer presses when the
 * cycle has concluded that the design has genuinely moved on.
 *
 * ## Why this is not part of completing an iteration
 *
 * Because it is a judgement, and an automatic version would take it away. Most cycles
 * do not produce a new design state — three iterations of tuning a cost curve are one
 * design change between them — and a platform that cut a version per cycle would fill a
 * game's history with versions nobody meant, making the numbers a count of how often
 * somebody pressed a button rather than of how much the design moved.
 *
 * So the sequence is: the designer completes the iteration, reads what it concluded, and
 * then decides. Section 30's loop ends with a person.
 *
 * ## Ordering
 *
 * The iteration must be completed first. A version cut from a cycle still in progress
 * would claim the design had moved on the strength of conclusions nobody had reached
 * yet — and since the cycle can still change, the claim might turn out to be about work
 * that was later abandoned.
 */
final class CreateNextGameVersion
{
    public function __construct(private readonly GameCatalogue $games) {}

    public function handle(User $creator, Iteration $iteration, ?string $name = null, ?string $description = null): GameVersion
    {
        $game = $iteration->game;

        if ($game === null) {
            throw new RuntimeException("Iteration [{$iteration->getKey()}] has no game to cut a version for.");
        }

        /*
         * Guarded here rather than only in the policy because a command has to hold
         * on its own. The policy says the same thing, so the interface never offers
         * the action on an open cycle — but a caller arriving another way is refused
         * all the same.
         */
        if (! $iteration->isClosed()) {
            throw IterationIsNotConcluded::forIteration($iteration->getKey());
        }

        return $this->games->createNextVersionOf($creator, $game, $name, $description);
    }
}
