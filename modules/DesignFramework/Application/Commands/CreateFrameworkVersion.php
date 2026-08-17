<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Application\DTOs\FrameworkVersionData;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Events\FrameworkVersionCreated;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;
use RuntimeException;

/**
 * Open a new edition of a framework.
 *
 * The operation that makes a published methodology able to change at all. v1 is
 * frozen the moment it is published; the way to reword a criterion or add a phase is
 * to create v2, edit it freely, and publish that. Games on v1 stay on v1 until their
 * designers say otherwise.
 *
 * The new version starts empty and as a draft. It is deliberately *not* a copy of the
 * previous one: cloning a version is a genuinely useful feature and a different
 * operation, with real decisions in it about what happens to content the author
 * wanted to drop. Guessing at it here would make "create v2" mean something nobody
 * asked for.
 *
 * ## Getting the number right when two people press the button at once
 *
 * Allocation is read-then-write, which is a race by construction. Three things make
 * it safe, in order of how much they are relied on:
 *
 * 1. A row lock on the *framework*, taken before the highest number is read. Two
 *    concurrent callers queue behind it, so the second reads a maximum that already
 *    includes the first one's insert. On PostgreSQL this is the whole answer.
 * 2. A unique index on (framework_id, version_number). Where the lock is weaker than
 *    it looks — SQLite, which the test suite runs on, ignores `FOR UPDATE` entirely —
 *    the database still refuses the duplicate.
 * 3. A bounded retry. A caller whose insert lost the race re-reads and takes the next
 *    number instead of failing.
 *
 * The retry limit exists so a genuine, repeating fault surfaces as an exception rather
 * than as a loop that never ends. It matters more here than for a game's versions,
 * because a duplicated framework version number would collide with the identifier
 * games cite.
 */
final class CreateFrameworkVersion
{
    /**
     * How many times to re-read and try again after losing a race.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
    ) {}

    public function handle(User $creator, Framework $framework, FrameworkVersionData $data): FrameworkVersion
    {
        $this->guard->ensureFrameworkAcceptsNewVersions($framework);

        $version = $this->allocate($creator, $framework, $data);

        $version->setRelation('framework', $framework);
        $version->setRelation('creator', $creator);

        event(new FrameworkVersionCreated(
            frameworkVersionId: $version->id,
            frameworkId: $framework->id,
            versionNumber: $version->version_number,
            createdBy: $creator->id,
        ));

        return $version;
    }

    /**
     * Take the next free number and write the version under it.
     */
    private function allocate(User $creator, Framework $framework, FrameworkVersionData $data): FrameworkVersion
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(function () use ($creator, $framework, $data): FrameworkVersion {
                    /*
                     * Lock the framework rather than the versions. There is no row to
                     * lock for a version that does not exist yet, and the framework is
                     * the thing whose "highest version" is being read and then depended
                     * on.
                     */
                    Framework::query()
                        ->whereKey($framework->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $next = $this->nextNumberFor($framework);

                    $version = new FrameworkVersion;

                    $version->fill([
                        'name' => $data->name,
                        'description' => $data->description,
                    ]);

                    $version->framework_id = $framework->getKey();
                    $version->version_number = $next->value;
                    $version->status = FrameworkStatus::default();
                    $version->created_by = $creator->id;

                    $version->save();

                    return $version;
                });
            } catch (UniqueConstraintViolationException) {
                /*
                 * Somebody else took this number in the window the lock did not cover.
                 * Nothing is wrong with the request — go round again and read the
                 * number they left behind.
                 */
                continue;
            }
        }

        throw new RuntimeException(
            "Could not allocate a version number for framework [{$framework->getKey()}] after ".self::MAX_ATTEMPTS.' attempts.',
        );
    }

    /**
     * The number that follows the framework's highest existing version.
     */
    private function nextNumberFor(Framework $framework): FrameworkVersionNumber
    {
        $highest = $this->frameworks->highestVersionNumber($framework);

        return $highest === null
            ? FrameworkVersionNumber::first()
            : FrameworkVersionNumber::fromInt($highest)->next();
    }
}
