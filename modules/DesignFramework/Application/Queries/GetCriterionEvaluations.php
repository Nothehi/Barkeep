<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;

/**
 * What a game has said about itself, most recently assessed first.
 *
 * Scoped to the adoption rather than to the game or the criterion, which is the read-side
 * half of the separation section 22 calls critical: these are one studio's answers to
 * questions every studio on the version was asked.
 *
 * Newest first because the useful reading is "what have we just reassessed?" — a designer
 * coming back to a phase page after a playtest wants to see what moved.
 */
final class GetCriterionEvaluations
{
    public function __construct(private readonly GameFrameworkRepository $adoptions) {}

    /**
     * @return Collection<int, CriterionEvaluation>
     */
    public function handle(GameFramework $adoption): Collection
    {
        return $this->adoptions->evaluationsOf($adoption);
    }
}
