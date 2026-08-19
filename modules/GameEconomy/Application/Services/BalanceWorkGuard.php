<?php

namespace Modules\GameEconomy\Application\Services;

use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameEconomy\Domain\Exceptions\BalanceProfileIsNotModifiable;
use Modules\GameEconomy\Domain\Exceptions\BalanceScenarioIsNotModifiable;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;

/**
 * The one place "may this still change?" is answered.
 *
 * Three things can freeze a balance configuration, and every write has to clear
 * all of them: the workspace may have stopped accepting changes, the game may
 * have been archived, and the profile itself may have been put away. The first
 * two are not this module's business, so they are delegated to GameDesign's own
 * guard rather than reimplemented — which stops "an archived game is read-only"
 * from having a second definition here that drifts from the first.
 *
 * The policy checks all of this before a request reaches a command, but a policy
 * only guards the HTTP door. Every command runs this too, so a caller arriving
 * another way — a console command, a queued job, a later module — cannot tune an
 * economy the product considers settled.
 *
 * Having exactly one implementation is the point. This module has more writable
 * child records than any other in the platform: resources, flows, actions,
 * costs, rewards, effects, variables, scenarios, overrides, assumptions and
 * observations all pass through here, and "archived profiles are read-only"
 * spread across thirty commands is thirty chances to forget it in the
 * thirty-first.
 */
final class BalanceWorkGuard
{
    public function __construct(private readonly GameModificationGuard $games) {}

    /**
     * Require that the given game may still have balance work recorded on it.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureGameAcceptsBalanceWork(Game $game): void
    {
        $this->games->ensureGameIsModifiable($game);
    }

    /**
     * Require that a profile's own details may still be changed.
     *
     * The game is checked first: if the project is closed, saying so is more
     * useful than complaining about the profile inside it.
     *
     * @throws BalanceProfileIsNotModifiable
     */
    public function ensureProfileIsModifiable(BalanceProfile $profile): void
    {
        $this->ensureGameOfProfileIsOpen($profile);

        if (! $profile->isModifiable()) {
            throw BalanceProfileIsNotModifiable::forStatus($profile->status);
        }
    }

    /**
     * Require that the configuration inside a profile may still be changed.
     *
     * The same window as the profile being editable today. Kept separate because
     * the two questions will come apart: a later "published" state that froze the
     * numbers while still allowing the description to be corrected would need
     * exactly this distinction, and adding it then would mean finding every one
     * of thirty call sites.
     *
     * @throws BalanceProfileIsNotModifiable
     */
    public function ensureProfileAcceptsConfiguration(BalanceProfile $profile): void
    {
        $this->ensureProfileIsModifiable($profile);
    }

    /**
     * Require that a scenario and its overrides may still be changed.
     *
     * Two gates: the profile around it, then the scenario itself. Reported as a
     * scenario problem even when the profile is what refused, because the caller
     * was acting on a scenario and that is the object they are looking at — the
     * message comes from the profile, so they are still told the real reason.
     *
     * @throws BalanceScenarioIsNotModifiable
     */
    public function ensureScenarioIsModifiable(BalanceScenario $scenario): void
    {
        $profile = $scenario->profile;

        if ($profile !== null) {
            $this->ensureGameOfProfileIsOpen($profile);

            if (! $profile->isModifiable()) {
                throw BalanceScenarioIsNotModifiable::becauseProfileIsClosed($profile->status->deniedReason());
            }
        }

        if (! $scenario->isModifiable()) {
            throw BalanceScenarioIsNotModifiable::forStatus($scenario->status);
        }
    }

    /**
     * Require that the game a profile belongs to is still open.
     *
     * Silently permits a profile whose game cannot be reached, which is the same
     * choice every guard in the platform makes: the relation is unloaded rather
     * than absent in practice, and refusing on a lazy-load miss would turn a
     * performance detail into a rule.
     *
     * @throws BalanceProfileIsNotModifiable
     */
    private function ensureGameOfProfileIsOpen(BalanceProfile $profile): void
    {
        $game = $profile->version?->game;

        if ($game === null) {
            return;
        }

        try {
            $this->games->ensureGameIsModifiable($game);
        } catch (GameIsNotModifiable $refusal) {
            throw BalanceProfileIsNotModifiable::becauseGameIsClosed($refusal->getMessage());
        }
    }
}
