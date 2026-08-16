<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\UpdatePlaytestData;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\GameDesign\GameCatalogue;
use RuntimeException;

/**
 * Change a playtest.
 *
 * Two operations wearing one name, and the difference is the point.
 *
 * A playtest's *plan* — what it set out to find out, which version it tested,
 * when it was meant to happen — is frozen the moment the playtest is over.
 * Editing it afterwards would rewrite the question the evidence was gathered
 * against, which is precisely the failure that makes old playtest records
 * worthless.
 *
 * Its *conclusion* is the opposite. Conclusions are written after the fact,
 * often days later once somebody has read back through the observations, so a
 * completed playtest has to stay open to that one field.
 *
 * So the two halves are guarded separately, and a request is checked against
 * whichever halves it actually touches — which is why the DTO records what was
 * sent rather than just what it holds.
 */
final class UpdatePlaytest
{
    public function __construct(
        private readonly GameCatalogue $catalogue,
        private readonly PlaytestModificationGuard $guard,
    ) {}

    public function handle(User $actor, Playtest $playtest, UpdatePlaytestData $data): Playtest
    {
        if ($data->touchesPlan()) {
            $this->guard->ensurePlaytestIsModifiable($playtest);
            $this->applyPlan($playtest, $data);
        }

        if ($data->touchesConclusion()) {
            $this->guard->ensurePlaytestAcceptsAnalysis($playtest);
            $playtest->conclusion = $data->conclusion;
        }

        $playtest->save();

        return $playtest;
    }

    /**
     * Write the fields that describe what the playtest set out to do.
     */
    private function applyPlan(Playtest $playtest, UpdatePlaytestData $data): void
    {
        if ($data->sent('title') && $data->title !== null) {
            $playtest->title = $data->title;
        }

        if ($data->sent('objective') && $data->objective !== null) {
            $playtest->objective = $data->objective;
        }

        if ($data->sent('hypothesis')) {
            $playtest->hypothesis = $data->hypothesis;
        }

        if ($data->sent('planned_at')) {
            $playtest->planned_at = $data->plannedAt;
        }

        if ($data->sent('game_version_id') && $data->gameVersionId !== null) {
            $playtest->game_version_id = $this->resolveVersion($playtest, $data->gameVersionId);
        }
    }

    /**
     * Resolve a replacement version, through the playtest's own game.
     *
     * Retargeting a playtest at a different version is legitimate while it is
     * still being planned — a designer cuts v4 before the session happens and
     * naturally wants to test that instead. It is refused once the playtest is
     * under way by the guard above, because by then the version is a statement
     * about what people actually played.
     *
     * The lookup goes through the game so the game/version invariant survives
     * the change. There is no path here on which a version from another game
     * can be attached.
     */
    private function resolveVersion(Playtest $playtest, string $versionId): string
    {
        $game = $playtest->game;

        if (! $game instanceof Game) {
            throw new RuntimeException("Playtest [{$playtest->getKey()}] was updated without its game loaded.");
        }

        $version = $this->catalogue->versionOf($game, $versionId);

        $playtest->setRelation('version', $version);

        return $version->getKey();
    }
}
