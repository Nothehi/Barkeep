<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\UpdatePrototypeData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\Prototype;

/**
 * Change a prototype's own details.
 *
 * Three fields, and the list of what is *not* here is the interesting part. The
 * status is absent because archiving is an action with its own endpoint and its own
 * event, not a value somebody can set — which keeps an irreversible move from being
 * one PATCH away from a reversible one. The design version is absent because a
 * prototype records the state it was built from, and rewriting that would change
 * what every iteration against it claims it was working with.
 *
 * The description is written only when the request actually mentioned it, which is
 * what lets "clear the description" and "leave it alone" be different requests —
 * both arrive as an absent value otherwise, and a command that could not tell them
 * apart would make one of the two impossible.
 *
 * No event is dispatched. Renaming a prototype is bookkeeping rather than something
 * that happened to the design, and an event announcing it would invite consumers to
 * treat a typo fix as design activity.
 */
final class UpdatePrototype
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, Prototype $prototype, UpdatePrototypeData $data): Prototype
    {
        $this->guard->ensurePrototypeIsModifiable($prototype);

        if ($data->isEmpty()) {
            return $prototype;
        }

        if ($data->name !== null) {
            $prototype->name = $data->name;
        }

        if ($data->changesDescription) {
            $prototype->description = $data->description;
        }

        if ($data->type !== null) {
            $prototype->type = $data->type;
        }

        $prototype->save();

        return $prototype;
    }
}
