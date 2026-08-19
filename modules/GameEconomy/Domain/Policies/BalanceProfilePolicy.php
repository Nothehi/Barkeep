<?php

namespace Modules\GameEconomy\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\ValueObjects\GameGrant;
use Modules\GameEconomy\Infrastructure\Authorization\GameAccess;
use Modules\Identity\Domain\Models\User;

/**
 * The single place balance access is decided.
 *
 * One policy for thirteen models, which is deliberate. A resource, a flow, an
 * action, a cost, a reward, an effect, a variable, a scenario, an override, an
 * assumption, an observation and a snapshot have no permissions of their own —
 * what decides whether any of them may be touched is whether the profile around
 * them is still open, and that is one question with one answer. Giving each its
 * own policy would be twelve copies of it, and twelve chances for the thirteenth
 * to be forgotten.
 *
 * Every answer comes from two inputs: what the game the profile ultimately
 * belongs to permits this account, and the profile's own status. Nothing here
 * reads a workspace, a membership or a role — GameDesign has already turned all
 * of that into the grant this policy is written against.
 *
 * The game is always taken from the profile rather than from the route, so "may
 * I tune this economy?" is answered against the game it actually belongs to. A
 * profile id from another game therefore fails here even if the URL names a game
 * the caller does have access to.
 *
 * Two kinds of "no" are returned, matching the convention the platform already
 * follows:
 *
 * - somebody who cannot see the game gets a 404, so profile ids cannot be used
 *   to discover what a studio is working on;
 * - somebody who can see it but may not act gets a 403, because they already
 *   know the profile exists and hiding it would only confuse them.
 *
 * Reading and writing come apart on archived games and archived profiles alike,
 * and that separation is the historical integrity rule in practice. An archived
 * profile still answers `view`, so the numbers a playtest ran against two years
 * ago stay readable; it refuses `configure`, so nothing can be tuned into a
 * configuration the studio has moved past.
 */
class BalanceProfilePolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * See the balance configurations of a design state.
     */
    public function viewAny(User $user, GameVersion $version): Response
    {
        return $this->grantForVersion($user, $version)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Start a new configuration for a design state.
     *
     * Open to every member of the workspace, because that is what game write
     * access already means. Tuning the economy is the work; restricting who may
     * record it to the people who administer the studio would be a strange shape
     * for a design tool.
     */
    public function create(User $user, GameVersion $version): Response
    {
        $game = $version->game;

        return $game === null
            ? $this->hide()
            : $this->requireWriteAccess($this->games->grantFor($user, $game));
    }

    /**
     * Read a configuration and everything scoped to it.
     */
    public function view(User $user, BalanceProfile $profile): Response
    {
        return $this->games->grantForProfile($user, $profile)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Change a profile's own name and description.
     */
    public function update(User $user, BalanceProfile $profile): Response
    {
        return $this->requireOpenProfile($user, $profile);
    }

    /**
     * Change anything inside the configuration.
     *
     * The ability nearly every write in the module runs against: resources,
     * flows, actions, costs, rewards, effects, variables, scenarios, overrides,
     * assumptions and observations all go through here.
     *
     * Gated on exactly the same thing as editing the profile itself, and kept
     * separate because the two questions will come apart. A later "published"
     * state that froze the configuration while still allowing the description to
     * be corrected would need precisely this distinction, and adding it then
     * would mean finding every call site.
     */
    public function configure(User $user, BalanceProfile $profile): Response
    {
        return $this->requireOpenProfile($user, $profile);
    }

    /**
     * Put a configuration into play.
     *
     * The same requirement as tuning it. Activating is not a privileged act —
     * it is how a designer says "these are the numbers now", and a permission
     * that made them ask somebody else would mean profiles stayed drafts.
     */
    public function activate(User $user, BalanceProfile $profile): Response
    {
        return $this->requireOpenProfile($user, $profile);
    }

    /**
     * Put a configuration away for good.
     *
     * Irreversible, which argues for care — but it is also the ordinary end of a
     * profile's life, done by whoever was tuning it. A permission that made a
     * designer ask somebody else to tidy up after them would simply mean nothing
     * ever got archived.
     */
    public function archive(User $user, BalanceProfile $profile): Response
    {
        return $this->requireOpenProfile($user, $profile);
    }

    /**
     * Freeze the configuration as it stands.
     *
     * Deliberately *not* gated on the profile still being open. An archived
     * profile is exactly the thing somebody wants to snapshot — "keep a copy of
     * what we shipped" is a reason to take one, not a reason to refuse. Nothing
     * about the live configuration changes when a snapshot is taken, so there is
     * nothing for the archived status to protect.
     */
    public function createSnapshot(User $user, BalanceProfile $profile): Response
    {
        return $this->requireWriteAccess($this->games->grantForProfile($user, $profile));
    }

    /**
     * Destroy a configuration outright.
     *
     * No route reaches this. Profiles are archived rather than deleted, so that
     * the numbers a playtest was run against survive — and the ability is defined
     * here because the policy is the right place to have already decided that
     * nobody may.
     */
    public function delete(User $user, BalanceProfile $profile): Response
    {
        return Response::deny(__('Balance profiles are archived rather than deleted.'));
    }

    /**
     * Require that the caller may change this particular configuration.
     *
     * Two gates, in order. The game has to be open to the caller and still
     * accepting changes; then the profile itself has to be one that can still
     * change. An archived profile is refused rather than hidden — the caller can
     * see it, so pretending it is gone would be a lie.
     */
    private function requireOpenProfile(User $user, BalanceProfile $profile): Response
    {
        $decision = $this->requireWriteAccess($this->games->grantForProfile($user, $profile));

        if (! $decision->allowed()) {
            return $decision;
        }

        return $profile->isModifiable()
            ? Response::allow()
            : Response::deny($profile->status->deniedReason());
    }

    /**
     * Turn a grant into a decision about recording something.
     */
    private function requireWriteAccess(GameGrant $grant): Response
    {
        if (! $grant->allowsReading()) {
            return $this->hide();
        }

        return $grant->allowsWriting()
            ? Response::allow()
            : Response::deny($grant->deniedReason ?? __('This game is not accepting changes.'));
    }

    /**
     * Resolve the caller's standing in the game behind a design state.
     */
    private function grantForVersion(User $user, GameVersion $version): GameGrant
    {
        $game = $version->game;

        return $game === null ? GameGrant::none() : $this->games->grantFor($user, $game);
    }

    /**
     * Deny in a way that does not admit the profile exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Balance profile not found.'));
    }
}
