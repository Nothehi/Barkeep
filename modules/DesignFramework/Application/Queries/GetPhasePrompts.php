<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The thinking questions of a version, or of one of its phases.

 * Returns the questions, never anybody's answers — those are {@see GetPromptResponses}.
 *
 * Always version-scoped, and the version is a required argument rather than a filter —
 * there is no "all prompts" query to call by mistake.
 *
 * Draft and archived content is excluded unless the caller asks for it, and only the
 * framework builder does, after the policy has confirmed the caller administers
 * frameworks. Resolution is unauthorized on purpose: finding content and deciding who may
 * see it are separate steps, and every caller runs the policy first.
 */
final class GetPhasePrompts
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, DesignPrompt>
     */
    public function handle(
        FrameworkVersion $version,
        ?DesignPhaseDefinition $phase = null,
        bool $includeUnpublished = false,
    ): Collection {
        /** @var Collection<int, DesignPrompt> $content */
        $content = $phase === null
            ? $this->frameworks->contentOf($version, DesignPrompt::class, $includeUnpublished)
            : $this->frameworks->contentInPhase($phase, DesignPrompt::class, $includeUnpublished);

        return $content;
    }
}
