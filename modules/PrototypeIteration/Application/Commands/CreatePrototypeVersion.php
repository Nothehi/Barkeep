<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\CreatePrototypeVersionData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Events\PrototypeVersionCreated;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;
use RuntimeException;

/**
 * Record a new state of a prototype.
 *
 * The number is allocated here and nowhere else. A caller never supplies one, so
 * there is no request that can claim v999, reuse v3, or renumber a history three
 * iterations already point at.
 *
 * This command is meant to be cheap to reach for, and that is a design requirement
 * rather than a nicety. The module refuses edits to a prototype version once
 * anything has been built on it, and that refusal is only reasonable if cutting the
 * next version costs nothing — so both fields are optional and there is no
 * ceremony. A designer told "you cannot change v3" should be one click from v4.
 *
 * ## Getting the number right when two people press the button at once
 *
 * Allocation is read-then-write, which is a race by construction. Three things make
 * it safe, in order of how much they are relied on — the same arrangement GameDesign
 * uses for game versions, for the same reasons:
 *
 * 1. A row lock on the *prototype*, taken before the highest number is read. Two
 *    concurrent callers queue behind it, so the second reads a maximum that already
 *    includes the first one's insert. On PostgreSQL this is the whole answer.
 * 2. A unique index on (prototype_id, version_number). Where the lock is weaker
 *    than it looks — SQLite, which the test suite runs on, ignores `FOR UPDATE`
 *    entirely — the database still refuses the duplicate.
 * 3. A bounded retry. A caller whose insert lost the race re-reads and takes the
 *    next number instead of failing, so losing a race costs a round trip rather
 *    than an error page.
 *
 * The retry limit exists so a genuine, repeating fault surfaces as an exception
 * rather than as a loop that never ends.
 */
final class CreatePrototypeVersion
{
    /**
     * How many times to re-read and try again after losing a race.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly PrototypeRepository $prototypes,
    ) {}

    public function handle(User $creator, Prototype $prototype, CreatePrototypeVersionData $data): PrototypeVersion
    {
        $this->guard->ensurePrototypeAcceptsVersions($prototype);

        $version = $this->allocate($creator, $prototype, $data);

        $version->setRelation('prototype', $prototype);
        $version->setRelation('creator', $creator);

        event(new PrototypeVersionCreated(
            prototypeVersionId: $version->id,
            prototypeId: $prototype->getKey(),
            gameId: $prototype->game_id,
            versionNumber: $version->version_number,
            createdBy: $creator->id,
        ));

        return $version;
    }

    /**
     * Take the next free number and write the version under it.
     */
    private function allocate(User $creator, Prototype $prototype, CreatePrototypeVersionData $data): PrototypeVersion
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(function () use ($creator, $prototype, $data): PrototypeVersion {
                    /**
                     * Lock the prototype rather than its versions. There is no row
                     * to lock for a version that does not exist yet, and the
                     * prototype is the thing whose "highest version" is being read
                     * and then depended on.
                     */
                    Prototype::query()
                        ->whereKey($prototype->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $next = $this->prototypes->nextVersionNumberFor($prototype);

                    $version = new PrototypeVersion;

                    $version->fill([
                        'name' => $data->name,
                        'description' => $data->description,
                    ]);

                    $version->prototype_id = $prototype->getKey();
                    $version->version_number = $next->value;
                    $version->created_by = $creator->id;

                    $version->save();

                    return $version;
                });
            } catch (UniqueConstraintViolationException) {
                /**
                 * Somebody else took this number in the window the lock did not
                 * cover. Nothing is wrong with the request — go round again and
                 * read the number they left behind.
                 */
                continue;
            }
        }

        throw new RuntimeException(
            "Could not allocate a version number for prototype [{$prototype->getKey()}] after ".self::MAX_ATTEMPTS.' attempts.',
        );
    }
}
