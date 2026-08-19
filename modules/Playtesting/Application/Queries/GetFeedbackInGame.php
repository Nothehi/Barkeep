<?php

namespace Modules\Playtesting\Application\Queries;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * One of a game's feedback entries, by id, wherever it was given.
 *
 * The counterpart to {@see GetObservationInGame} for what the players said rather
 * than what the designers noticed, published for the same reason and scoped the
 * same way: through the game, so a citation cannot reach across a workspace
 * boundary, and returning null rather than distinguishing "not yours" from "no
 * such thing".
 *
 * @see PlaytestRepository::findFeedbackInGame()
 */
final class GetFeedbackInGame
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    public function handle(Game $game, string $feedbackId): ?PlaytestFeedback
    {
        return $this->playtests->findFeedbackInGame($game, $feedbackId);
    }
}
