<?php

namespace Modules\Playtesting\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * What participants said about a session, oldest first.
 *
 * Returned separately from the observations rather than merged with them. The
 * screen interleaves the two into a timeline, but the distinction between
 * "somebody noticed" and "somebody said" has to survive as far as the reader —
 * merging them here would lose it before it got there.
 *
 * @see PlaytestRepository::feedbackOf()
 */
final class GetFeedback
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    /**
     * @return Collection<int, PlaytestFeedback>
     */
    public function handle(PlaytestSession $session): Collection
    {
        return $this->playtests->feedbackOf($session);
    }
}
