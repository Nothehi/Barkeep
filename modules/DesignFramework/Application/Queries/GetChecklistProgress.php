<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Application\DTOs\ChecklistProgress;
use Modules\DesignFramework\Application\Services\FrameworkProgressCalculator;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;

/**
 * A game's checklists, paired with which of their requirements it has met.
 *
 * The framework owns the lists; the game owns the ticks; this is the one read that puts
 * them side by side. Doing it here rather than in a resource is what keeps the client from
 * having to join two collections by id and getting the required-versus-optional distinction
 * wrong.
 *
 * The lists come from the adopted version, so a game on v1 sees v1's checklists however
 * many editions have shipped since.
 */
final class GetChecklistProgress
{
    public function __construct(
        private readonly GameFrameworkRepository $adoptions,
        private readonly FrameworkProgressCalculator $calculator,
    ) {}

    /**
     * @param  Collection<int, Checklist>|null  $checklists  a narrower set, when a phase
     *                                                       page only wants its own
     * @return array<int, ChecklistProgress>
     */
    public function handle(GameFramework $adoption, ?Collection $checklists = null): array
    {
        return $this->calculator->forChecklists(
            $adoption,
            $checklists ?? $this->adoptions->checklistsOf($adoption),
        );
    }
}
