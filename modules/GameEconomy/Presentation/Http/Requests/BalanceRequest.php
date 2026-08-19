<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for balance requests.
 *
 * Two rules hold for every subclass, and together they are the module's whole
 * defence against tuning somebody else's economy:
 *
 * - the workspace, the game, the design version, the profile and every child
 *   record come from resolved route bindings, never from the request body, so a
 *   caller does not get to name what their permissions are checked against;
 * - the answer is the policy's {@see Response}, not a boolean, so its choice
 *   between "you may not" and "there is no such thing" survives all the way to
 *   the status code.
 *
 * The bindings themselves are chained — see `GameEconomyServiceProvider` — so a
 * profile belonging to another design state, or a variable belonging to another
 * configuration, fails to resolve before any of this runs.
 *
 * Three identifiers escape that arrangement because they have no route segment
 * of their own: the resource a flow, cost or reward names, the resource or
 * action a variable is about, and the variable a scenario overrides. All three
 * are checked explicitly, by rule objects that resolve them through the profile
 * that owns them — which is why those rules exist at all, and why none of them
 * is an `exists` clause.
 *
 * Nearly every write in this module authorizes against `configure` rather than
 * against an ability of its own. "May the configuration inside this profile be
 * changed?" is one question with one answer, and asking it once is what stops
 * eleven kinds of record from drifting apart as the rules change.
 */
abstract class BalanceRequest extends FormRequest
{
    /**
     * The workspace this request is scoped to.
     */
    protected function workspace(): Workspace
    {
        $workspace = $this->route('workspace');

        if (! $workspace instanceof Workspace) {
            throw new RuntimeException(static::class.' was used on a route without a bound workspace.');
        }

        return $workspace;
    }

    /**
     * The game this request is about.
     */
    protected function game(): Game
    {
        $game = $this->route('game');

        if (! $game instanceof Game) {
            throw new RuntimeException(static::class.' was used on a route without a bound game.');
        }

        return $game;
    }

    /**
     * The design state this request is about.
     */
    protected function version(): GameVersion
    {
        $version = $this->route('version');

        if (! $version instanceof GameVersion) {
            throw new RuntimeException(static::class.' was used on a route without a bound game version.');
        }

        return $version;
    }

    /**
     * The balance configuration this request is about.
     */
    protected function profile(): BalanceProfile
    {
        $profile = $this->route('profile');

        if (! $profile instanceof BalanceProfile) {
            throw new RuntimeException(static::class.' was used on a route without a bound balance profile.');
        }

        return $profile;
    }

    /**
     * The profile a child record belongs to.
     *
     * Most child routes carry `{profile}` themselves, but the ones nested under
     * an action do not need to — the action already knows. This reads whichever
     * is available so a subclass does not have to care which shape of URL it is
     * on.
     */
    protected function owningProfile(): BalanceProfile
    {
        $profile = $this->route('profile');

        if ($profile instanceof BalanceProfile) {
            return $profile;
        }

        $action = $this->route('economyAction');

        if ($action instanceof EconomyAction && $action->profile !== null) {
            return $action->profile;
        }

        throw new RuntimeException(static::class.' was used on a route without a bound balance profile.');
    }

    /**
     * The resource this request is about.
     */
    protected function resourceType(): ResourceType
    {
        $resource = $this->route('resourceType');

        if (! $resource instanceof ResourceType) {
            throw new RuntimeException(static::class.' was used on a route without a bound resource.');
        }

        return $resource;
    }

    /**
     * The declared movement this request is about.
     */
    protected function flow(): ResourceFlow
    {
        $flow = $this->route('flow');

        if (! $flow instanceof ResourceFlow) {
            throw new RuntimeException(static::class.' was used on a route without a bound resource flow.');
        }

        return $flow;
    }

    /**
     * The action this request is about.
     *
     * Named for the route parameter rather than shortened to `action()`, which
     * would collide with `FormRequest`'s own idea of a route action.
     */
    protected function economyAction(): EconomyAction
    {
        $action = $this->route('economyAction');

        if (! $action instanceof EconomyAction) {
            throw new RuntimeException(static::class.' was used on a route without a bound action.');
        }

        return $action;
    }

    /**
     * The cost line this request is about.
     */
    protected function cost(): ActionCost
    {
        $cost = $this->route('cost');

        if (! $cost instanceof ActionCost) {
            throw new RuntimeException(static::class.' was used on a route without a bound cost.');
        }

        return $cost;
    }

    /**
     * The reward line this request is about.
     */
    protected function reward(): ActionReward
    {
        $reward = $this->route('reward');

        if (! $reward instanceof ActionReward) {
            throw new RuntimeException(static::class.' was used on a route without a bound reward.');
        }

        return $reward;
    }

    /**
     * The effect this request is about.
     */
    protected function effect(): ActionEffect
    {
        $effect = $this->route('effect');

        if (! $effect instanceof ActionEffect) {
            throw new RuntimeException(static::class.' was used on a route without a bound effect.');
        }

        return $effect;
    }

    /**
     * The tunable number this request is about.
     */
    protected function variable(): BalanceVariable
    {
        $variable = $this->route('variable');

        if (! $variable instanceof BalanceVariable) {
            throw new RuntimeException(static::class.' was used on a route without a bound variable.');
        }

        return $variable;
    }

    /**
     * The hypothetical this request is about.
     */
    protected function scenario(): BalanceScenario
    {
        $scenario = $this->route('scenario');

        if (! $scenario instanceof BalanceScenario) {
            throw new RuntimeException(static::class.' was used on a route without a bound scenario.');
        }

        return $scenario;
    }

    /**
     * The scenario override this request is about.
     */
    protected function override(): ScenarioVariable
    {
        $override = $this->route('override');

        if (! $override instanceof ScenarioVariable) {
            throw new RuntimeException(static::class.' was used on a route without a bound override.');
        }

        return $override;
    }

    /**
     * The assumption this request is about.
     */
    protected function assumption(): BalanceAssumption
    {
        $assumption = $this->route('assumption');

        if (! $assumption instanceof BalanceAssumption) {
            throw new RuntimeException(static::class.' was used on a route without a bound assumption.');
        }

        return $assumption;
    }

    /**
     * The balance observation this request is about.
     *
     * The route parameter is `{balanceObservation}` rather than `{observation}`
     * because Playtesting already binds the shorter name, and route binder names
     * are global to the application — see `.ai/rules/providers.md`.
     */
    protected function balanceObservation(): BalanceObservation
    {
        $observation = $this->route('balanceObservation');

        if (! $observation instanceof BalanceObservation) {
            throw new RuntimeException(static::class.' was used on a route without a bound observation.');
        }

        return $observation;
    }

    /**
     * The signed in account.
     */
    protected function actor(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Run an ability against the policy, with whatever it is about.
     *
     * @param  array<int, mixed>  $arguments
     */
    protected function inspect(string $ability, array $arguments): Response
    {
        $user = $this->actor();

        if ($user === null) {
            return Response::deny();
        }

        return Gate::forUser($user)->inspect($ability, $arguments);
    }

    /**
     * Run a profile ability against the bound design state.
     */
    protected function inspectVersion(string $ability): Response
    {
        return $this->inspect($ability, [BalanceProfile::class, $this->version()]);
    }

    /**
     * Run an ability against the bound profile.
     */
    protected function inspectProfile(string $ability): Response
    {
        return $this->inspect($ability, [$this->profile()]);
    }

    /**
     * Require the right to change the configuration this request is inside.
     *
     * The ability nearly every write in the module runs against.
     */
    protected function inspectConfiguration(): Response
    {
        return $this->inspect('configure', [$this->owningProfile()]);
    }
}
