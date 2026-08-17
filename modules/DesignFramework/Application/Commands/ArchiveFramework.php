<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkTransition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\Identity\Domain\Models\User;

/**
 * Retire a methodology.
 *
 * Terminal, and it takes nobody's work with it. Games already following the
 * framework's versions keep reading them and keep every evaluation, completion and
 * written answer they gathered — which is the whole reason a framework is archived
 * rather than deleted, and why the database refuses to remove a version anything has
 * adopted.
 *
 * What it stops is anything new: no further versions, no further adoptions, and no
 * changes to the framework's own record.
 *
 * There is deliberately no event. Nothing outside this module needs to hear that a
 * methodology was retired — games keep working, and the fact is readable from the
 * framework's status by anybody who cares. Publishing is the announcement; this is
 * housekeeping.
 */
final class ArchiveFramework
{
    public function handle(User $actor, Framework $framework): Framework
    {
        DB::transaction(function () use ($framework): void {
            $fresh = Framework::query()
                ->whereKey($framework->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(FrameworkStatus::Archived)) {
                throw InvalidFrameworkTransition::between($fresh->status, FrameworkStatus::Archived);
            }

            $fresh->forceFill(['status' => FrameworkStatus::Archived])->save();

            $framework->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        return $framework;
    }
}
