<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Events\PrototypeArchived;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidPrototypeTransition;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * Put a prototype away for good.
 *
 * Archiving rather than deleting, and the reason is the module's whole purpose. A
 * prototype's versions are what the design history points at: remove one and every
 * iteration run against it loses the answer to "what was actually on the table".
 * So this is the only way a prototype leaves circulation, it is terminal, and there
 * is no inverse command — a studio picking the approach back up creates a new
 * prototype, which is also how they would describe it.
 *
 * An archived prototype stays fully readable. That asymmetry is deliberate and it
 * is inherited from the game above it: `view` survives archival and `update` does
 * not, so a two-year-old iteration stays legible while nothing new can be attached
 * to work that has been put away.
 *
 * The move is decided under a row lock and against the status read inside it, not
 * against whatever the caller was looking at. Two people pressing "Archive" at the
 * same moment therefore produce one winner and one honest refusal, instead of a
 * second event announcing an archival that had already happened.
 */
final class ArchivePrototype
{
    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly PrototypeRepository $prototypes,
    ) {}

    public function handle(User $actor, Prototype $prototype): Prototype
    {
        $this->guard->ensurePrototypeIsModifiable($prototype);

        $archivedAt = now()->toImmutable();

        DB::transaction(function () use ($prototype): void {
            $fresh = Prototype::query()
                ->whereKey($prototype->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(PrototypeStatus::Archived)) {
                throw InvalidPrototypeTransition::between($fresh->status, PrototypeStatus::Archived);
            }

            $fresh->forceFill(['status' => PrototypeStatus::Archived]);
            $fresh->save();

            /*
             * Carry the saved row back onto the instance the caller holds, so what
             * gets rendered afterwards is the state that was written rather than
             * the one that was read before the lock.
             */
            $prototype->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new PrototypeArchived(
            prototypeId: $prototype->id,
            gameId: $prototype->game_id,
            versionCount: $this->prototypes->countVersionsOf($prototype),
            archivedBy: $actor->id,
            archivedAt: $archivedAt->toDateTimeImmutable(),
        ));

        return $prototype;
    }
}
