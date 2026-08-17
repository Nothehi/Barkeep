<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a criterion among its siblings.
 *
 * The order criteria are assessed in is part of the methodology — an author who puts
 * "is the core loop understandable?" before "is downtime acceptable?" is saying which
 * question to settle first.
 *
 * The sibling set is scoped by phase as well as by version, so content filed under no
 * phase orders independently of content filed under one. Two criterions in different
 * phases both being "position 1" is correct.
 *
 * All the arithmetic lives in `ContentSequencer`; this command proves the version is
 * still a draft and names the set.
 */
final class ReorderCriterion
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, DesignCriterion $criterion, int $position): DesignCriterion
    {
        $this->guard->ensureContentIsModifiable($criterion);

        $version = $criterion->version;

        if ($version !== null) {
            $this->sequencer->move(
                $criterion,
                $this->frameworks->contentSiblings(
                    $version,
                    DesignCriterion::class,
                    $criterion->phase_id === null ? null : (string) $criterion->phase_id,
                ),
                $position,
            );
        }

        return $criterion;
    }
}
