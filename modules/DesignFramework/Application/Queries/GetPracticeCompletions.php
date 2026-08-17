<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PracticeCompletion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;

/**
 * The framework activities a game has carried out, most recent first.
 *
 * Reads as a log of work done, which is the point: the notes on a completion — "we ran it
 * with four people and the market never emptied" — are what a designer rereads while trying
 * to remember why they changed the resource costs.
 */
final class GetPracticeCompletions
{
    public function __construct(private readonly GameFrameworkRepository $adoptions) {}

    /**
     * @return Collection<int, PracticeCompletion>
     */
    public function handle(GameFramework $adoption): Collection
    {
        return $this->adoptions->practiceCompletionsOf($adoption);
    }
}
