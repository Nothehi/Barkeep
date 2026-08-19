<?php

namespace Modules\GameEconomy\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Queries\GetBalanceProfile;
use Modules\GameEconomy\Domain\Exceptions\EconomyRuleViolation;
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
use Modules\GameEconomy\Domain\Policies\BalanceProfilePolicy;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * Wires the GameEconomy bounded context into the application.
 *
 * This module owns the quantitative model of a game — what resources exist, what
 * moves them, what things cost — and everything that decides how one of those
 * records is found, who may touch it and how its rules surface over HTTP is
 * configured here rather than being spread across the application's own
 * providers.
 */
class GameEconomyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module.
     */
    public function boot(): void
    {
        $this->configureRouteBindings();
        $this->configureAuthorization();
        $this->configureExceptionRendering();
    }

    /**
     * Teach the router how to find everything the module addresses.
     *
     * Twelve bindings, each resolving a record *through* the one above it. The
     * game version was already resolved through a game by GameDesign's own
     * binding, and the game through a workspace, so the whole ownership chain —
     *
     *     workspace → game → version → profile → resource | flow | action | …
     *                                          → action → cost | reward | effect
     *                                          → scenario → override
     *
     * — is walked by the router before any handler or policy runs.
     *
     * The security property that buys is worth stating plainly. There is no route
     * in this module on which an identifier is looked up without its parent. A
     * variable belonging to somebody else's configuration does not 403; it fails
     * to resolve, and the request 404s before a controller sees it. That is why
     * the module's ids can be opaque uuids in a URL without any of them being a
     * capability.
     *
     * ## The parameter names
     *
     * Route binder names are global to the application — Laravel keeps one binder
     * per parameter name, and the provider registered last silently wins. Four
     * names here were chosen with that in mind:
     *
     * - `{version}` is *not* bound here. GameDesign already resolves a game's
     *   design state under that name, which is exactly what this module needs;
     *   claiming it again would break GameDesign's own chain and DesignFramework's
     *   delegation with it. See `.ai/rules/providers.md`.
     * - `{economyAction}` rather than `{action}`, because "action" is the most
     *   overloaded word in the application and a bare binding on it would be a
     *   trap for every module built after this one.
     * - `{resourceType}` rather than `{resource}`, for the same reason.
     * - `{balanceObservation}` rather than `{observation}`, because `{observation}`
     *   belongs to Playtesting: binding it here would break every playtest
     *   evidence route in the application.
     *
     * Explicit bindings run in the order the parameters appear in the URL, and the
     * URLs are nested in the same order as the chain — which is what makes "the
     * parent is already a model by the time the child resolves" true rather than
     * hopeful.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('profile', function (string $value, RouteInstance $route): BalanceProfile {
            $version = $route->parameter('version');

            $profile = $version instanceof GameVersion && Str::isUuid($value)
                ? $this->app->make(GetBalanceProfile::class)->handle($version, $value)
                : null;

            return $profile ?? throw (new ModelNotFoundException)->setModel(BalanceProfile::class, [$value]);
        });

        Route::bind('resourceType', fn (string $value, RouteInstance $route): ResourceType => $this->resolveInProfile(
            $route,
            $value,
            ResourceType::class,
            fn (EconomyRepository $economy, BalanceProfile $profile): ?ResourceType => $economy
                ->findResourceInProfile($profile, $value),
        ));

        Route::bind('flow', fn (string $value, RouteInstance $route): ResourceFlow => $this->resolveInProfile(
            $route,
            $value,
            ResourceFlow::class,
            fn (EconomyRepository $economy, BalanceProfile $profile): ?ResourceFlow => $economy
                ->findFlowInProfile($profile, $value),
        ));

        Route::bind('economyAction', fn (string $value, RouteInstance $route): EconomyAction => $this->resolveInProfile(
            $route,
            $value,
            EconomyAction::class,
            fn (EconomyRepository $economy, BalanceProfile $profile): ?EconomyAction => $economy
                ->findActionInProfile($profile, $value),
        ));

        Route::bind('variable', fn (string $value, RouteInstance $route): BalanceVariable => $this->resolveInProfile(
            $route,
            $value,
            BalanceVariable::class,
            fn (EconomyRepository $economy, BalanceProfile $profile): ?BalanceVariable => $economy
                ->findVariableInProfile($profile, $value),
        ));

        Route::bind('scenario', fn (string $value, RouteInstance $route): BalanceScenario => $this->resolveInProfile(
            $route,
            $value,
            BalanceScenario::class,
            fn (EconomyRepository $economy, BalanceProfile $profile): ?BalanceScenario => $economy
                ->findScenarioInProfile($profile, $value),
        ));

        Route::bind('cost', fn (string $value, RouteInstance $route): ActionCost => $this->resolveInAction(
            $route,
            $value,
            ActionCost::class,
            fn (EconomyRepository $economy, EconomyAction $action): ?ActionCost => $economy
                ->findCostInAction($action, $value),
        ));

        Route::bind('reward', fn (string $value, RouteInstance $route): ActionReward => $this->resolveInAction(
            $route,
            $value,
            ActionReward::class,
            fn (EconomyRepository $economy, EconomyAction $action): ?ActionReward => $economy
                ->findRewardInAction($action, $value),
        ));

        Route::bind('effect', fn (string $value, RouteInstance $route): ActionEffect => $this->resolveInAction(
            $route,
            $value,
            ActionEffect::class,
            fn (EconomyRepository $economy, EconomyAction $action): ?ActionEffect => $economy
                ->findEffectInAction($action, $value),
        ));

        /*
         * The one binding that resolves through a scenario rather than a profile.
         * An override only means anything inside the hypothetical that states it,
         * which is exactly the scope the lookup uses.
         */
        Route::bind('override', function (string $value, RouteInstance $route): ScenarioVariable {
            $scenario = $route->parameter('scenario');

            $override = $scenario instanceof BalanceScenario && Str::isUuid($value)
                ? $this->app->make(EconomyRepository::class)->findOverrideInScenario($scenario, $value)
                : null;

            return $override ?? throw (new ModelNotFoundException)->setModel(ScenarioVariable::class, [$value]);
        });

        Route::bind('assumption', function (string $value, RouteInstance $route): BalanceAssumption {
            $profile = $route->parameter('profile');

            $assumption = $profile instanceof BalanceProfile && Str::isUuid($value)
                ? $this->app->make(BalanceProfileRepository::class)->findAssumptionInProfile($profile, $value)
                : null;

            return $assumption ?? throw (new ModelNotFoundException)->setModel(BalanceAssumption::class, [$value]);
        });

        Route::bind('balanceObservation', function (string $value, RouteInstance $route): BalanceObservation {
            $profile = $route->parameter('profile');

            $observation = $profile instanceof BalanceProfile && Str::isUuid($value)
                ? $this->app->make(BalanceProfileRepository::class)->findObservationInProfile($profile, $value)
                : null;

            return $observation ?? throw (new ModelNotFoundException)->setModel(BalanceObservation::class, [$value]);
        });
    }

    /**
     * Resolve one of a configuration's records, or 404.
     *
     * Six bindings differ only in which lookup they call, so they share this. The
     * uuid check in front of the query is not cosmetic: PostgreSQL raises rather
     * than returning nothing when a uuid column is compared against a string that
     * is not one, which would turn a mistyped URL into a 500.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(EconomyRepository, BalanceProfile): ?TModel  $lookup
     * @return TModel
     */
    private function resolveInProfile(RouteInstance $route, string $value, string $model, callable $lookup)
    {
        $profile = $route->parameter('profile');

        $resolved = $profile instanceof BalanceProfile && Str::isUuid($value)
            ? $lookup($this->app->make(EconomyRepository::class), $profile)
            : null;

        return $resolved ?? throw (new ModelNotFoundException)->setModel($model, [$value]);
    }

    /**
     * Resolve one of an action's lines, or 404.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(EconomyRepository, EconomyAction): ?TModel  $lookup
     * @return TModel
     */
    private function resolveInAction(RouteInstance $route, string $value, string $model, callable $lookup)
    {
        $action = $route->parameter('economyAction');

        $resolved = $action instanceof EconomyAction && Str::isUuid($value)
            ? $lookup($this->app->make(EconomyRepository::class), $action)
            : null;

        return $resolved ?? throw (new ModelNotFoundException)->setModel($model, [$value]);
    }

    /**
     * Point the gate at the module's policy.
     *
     * One policy for thirteen models, which is deliberate. A resource, a flow, an
     * action, a cost, a reward, an effect, a variable, a scenario, an override, an
     * assumption, an observation and a snapshot have no permissions of their own —
     * what decides whether any of them may be touched is whether the profile
     * around them is still open, and that is one question with one answer. Giving
     * each its own policy would be twelve copies of it.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(BalanceProfile::class, BalanceProfilePolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch at
     * each call site, so a rule added later surfaces correctly without any
     * controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which is
     * what puts "that resource belongs to a different balance profile" next to the
     * resource picker instead of in a toast — and what puts "this action already
     * costs wood, edit that line instead" where the person who just tried to add
     * it will read it.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (EconomyRuleViolation $violation, Request $request) {
                $field = $violation->field();

                if ($field !== null && ! $request->expectsJson()) {
                    throw ValidationException::withMessages([$field => $violation->getMessage()]);
                }

                if ($request->expectsJson()) {
                    return response()->json(
                        ['message' => $violation->getMessage()],
                        $violation->status(),
                    );
                }

                return back()->withErrors(['balance' => $violation->getMessage()]);
            });
        });
    }
}
