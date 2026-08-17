<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a practice among its siblings.
 *
 * Practices are the content type where order is closest to being a sequence: "create a
 * paper prototype" before "run a two-player test" is advice, and reordering them says
 * something.
 *
 * The sibling set is scoped by phase as well as by version, so content filed under no
 * phase orders independently of content filed under one. Two practices in different
 * phases both being "position 1" is correct.
 *
 * All the arithmetic lives in `ContentSequencer`; this command proves the version is
 * still a draft and names the set.
 */
final class ReorderPractice
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, DesignPractice $practice, int $position): DesignPractice
    {
        $this->guard->ensureContentIsModifiable($practice);

        $version = $practice->version;

        if ($version !== null) {
            $this->sequencer->move(
                $practice,
                $this->frameworks->contentSiblings(
                    $version,
                    DesignPractice::class,
                    $practice->phase_id === null ? null : (string) $practice->phase_id,
                ),
                $position,
            );
        }

        return $practice;
    }
}
