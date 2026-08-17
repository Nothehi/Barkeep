<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkTransition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Retire one edition of a framework.
 *
 * What this stops is *new* adoptions. Games already following the version keep reading
 * it and keep everything they recorded against it — the database will not let the row
 * go while anything points at it, and that refusal is deliberate rather than
 * incidental.
 *
 * The usual reason to archive a version is that a better one exists. Publishing v2
 * does not retire v1 automatically, because plenty of studios are mid-way through it
 * and an author may well want both adoptable for a while. Retiring v1 is a separate
 * decision, made when they are ready to stop new projects starting on it.
 *
 * Reachable from a draft as well as from a published version: abandoning an edition
 * somebody started and thought better of is exactly what archiving a draft means.
 */
final class ArchiveFrameworkVersion
{
    public function handle(User $actor, FrameworkVersion $version): FrameworkVersion
    {
        DB::transaction(function () use ($version): void {
            $fresh = FrameworkVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(FrameworkStatus::Archived)) {
                throw InvalidFrameworkTransition::between($fresh->status, FrameworkStatus::Archived);
            }

            $fresh->forceFill(['status' => FrameworkStatus::Archived])->save();

            $version->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        return $version;
    }
}
