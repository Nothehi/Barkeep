<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a prompt among its siblings.
 *
 * The sibling set is scoped by phase as well as by version, so content filed under no
 * phase orders independently of content filed under one. Two prompts in different
 * phases both being "position 1" is correct.
 *
 * All the arithmetic lives in `ContentSequencer`; this command proves the version is
 * still a draft and names the set.
 */
final class ReorderPrompt
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, DesignPrompt $prompt, int $position): DesignPrompt
    {
        $this->guard->ensureContentIsModifiable($prompt);

        $version = $prompt->version;

        if ($version !== null) {
            $this->sequencer->move(
                $prompt,
                $this->frameworks->contentSiblings(
                    $version,
                    DesignPrompt::class,
                    $prompt->phase_id === null ? null : (string) $prompt->phase_id,
                ),
                $position,
            );
        }

        return $prompt;
    }
}
