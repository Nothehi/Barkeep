<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\UpdateIterationData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Application\Services\PrototypeCatalogue;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\GameDesign\GameCatalogue;

/**
 * Change an iteration's plan while the cycle is still open.
 *
 * The guard is doing the important work here. A completed or cancelled iteration
 * refuses every field on this command, which is section 53's historical integrity
 * rule at its most literal: the objective and hypothesis of a finished cycle are
 * what its changes and decisions were made against, and editing them afterwards
 * would leave a record whose conclusions no longer answer its own question.
 *
 * Either version may be corrected while the cycle is open, and both are re-proved
 * through the same catalogues `CreateIteration` used. That is not belt-and-braces:
 * an update is a second door into the same invariant, and a command that trusted an
 * id because it had been validated once would be the place the forgery got in.
 *
 * No event is dispatched. Correcting a plan before the work starts is bookkeeping,
 * and the events in this module mark things that happened to the design.
 */
final class UpdateIteration
{
    public function __construct(
        private readonly GameCatalogue $games,
        private readonly PrototypeCatalogue $prototypes,
        private readonly DesignWorkGuard $guard,
    ) {}

    public function handle(User $actor, Iteration $iteration, UpdateIterationData $data): Iteration
    {
        $this->guard->ensureIterationIsModifiable($iteration);

        if ($data->isEmpty()) {
            return $iteration;
        }

        $game = $iteration->game;

        if ($data->gameVersionId !== null && $game !== null) {
            $iteration->game_version_id = $this->games->versionOf($game, $data->gameVersionId)->getKey();
        }

        if ($data->prototypeVersionId !== null && $game !== null) {
            $iteration->prototype_version_id = $this->prototypes
                ->versionOf($game, $data->prototypeVersionId)
                ->getKey();
        }

        if ($data->title !== null) {
            $iteration->title = $data->title;
        }

        if ($data->objective !== null) {
            $iteration->objective = $data->objective;
        }

        if ($data->changesHypothesis) {
            $iteration->hypothesis = $data->hypothesis;
        }

        $iteration->save();

        /*
         * The relations may now point at the versions that were replaced, and the
         * caller is about to render them. Dropping them is cheaper and safer than
         * re-resolving: whatever reads them next reloads from what was actually
         * written.
         */
        $iteration->unsetRelation('version')->unsetRelation('prototypeVersion');

        return $iteration;
    }
}
