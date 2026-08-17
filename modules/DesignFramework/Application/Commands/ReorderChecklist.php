<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a checklist among its siblings.
 *
 * Only relevant when a phase has more than one, which is common at the end of an arc:
 * "playtest readiness" and "production readiness" often sit together.
 *
 * The sibling set is scoped by phase as well as by version, so content filed under no
 * phase orders independently of content filed under one. Two checklists in different
 * phases both being "position 1" is correct.
 *
 * All the arithmetic lives in `ContentSequencer`; this command proves the version is
 * still a draft and names the set.
 */
final class ReorderChecklist
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, Checklist $checklist, int $position): Checklist
    {
        $this->guard->ensureContentIsModifiable($checklist);

        $version = $checklist->version;

        if ($version !== null) {
            $this->sequencer->move(
                $checklist,
                $this->frameworks->contentSiblings(
                    $version,
                    Checklist::class,
                    $checklist->phase_id === null ? null : (string) $checklist->phase_id,
                ),
                $position,
            );
        }

        return $checklist;
    }
}
