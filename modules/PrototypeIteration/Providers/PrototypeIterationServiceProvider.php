<?php

namespace Modules\PrototypeIteration\Providers;

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
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Queries\GetIteration;
use Modules\PrototypeIteration\Application\Queries\GetPrototype;
use Modules\PrototypeIteration\Application\Queries\GetPrototypeVersion;
use Modules\PrototypeIteration\Domain\Exceptions\IterationRuleViolation;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\Policies\IterationPolicy;
use Modules\PrototypeIteration\Domain\Policies\PrototypePolicy;
use Modules\PrototypeIteration\Domain\ValueObjects\PrototypeVersionNumber;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * Wires the PrototypeIteration bounded context into the application.
 *
 * This module records the actual design work — what was built, what was changed, what was decided —
 * and everything that decides how one of those records is found, who may touch it and how its rules
 * surface over HTTP is configured here rather than being spread across the application's own
 * providers.
 */
class PrototypeIterationServiceProvider extends ServiceProvider
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
     * Eight bindings, each resolving a record *through* the one above it. The game itself was
     * already resolved through a workspace by GameDesign's own binding, so both ownership chains —
     *
     *     workspace → game → prototype → version → artifact
     *     workspace → game → iteration → change | experiment | decision | playtest link
     *
     * — are walked by the router before any handler or policy runs.
     *
     * The security property that buys is worth stating plainly. There is no route in this module on
     * which an identifier is looked up without its parent. A decision belonging to somebody else's
     * iteration does not 403; it fails to resolve, and the request 404s before a controller sees it.
     * That is why the module's ids can be opaque uuids in a URL without any of them being a
     * capability.
     *
     * ## The parameter names
     *
     * Route binder names are global to the application — Laravel keeps one binder per parameter
     * name, and the provider registered last silently wins. Two names here were chosen with that in
     * mind:
     *
     * - `{prototypeVersion}` rather than `{version}`, because `{version}` is already bound twice
     *   (GameDesign resolves a game's iteration, DesignFramework an edition of a methodology) and a
     *   third claim on it would break one of those chains outright. It is also clearer on a route
     *   that carries a game version and a prototype version in the same URL.
     * - `{link}` rather than `{playtest}` for detaching, because `{playtest}` belongs to Playtesting
     *   and binding it here would hand this module's controllers another context's Eloquent model.
     *   Addressing the association instead keeps the whole route inside this module.
     *
     * Explicit bindings run in the order the parameters appear in the URL, and the URLs are nested
     * in the same order as the chains — which is what makes "the parent is already a model by the
     * time the child resolves" true rather than hopeful.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('prototype', function (string $value, RouteInstance $route): Prototype {
            $game = $route->parameter('game');

            $prototype = $game instanceof Game && Str::isUuid($value)
                ? $this->app->make(GetPrototype::class)->handle($game, $value)
                : null;

            return $prototype ?? throw (new ModelNotFoundException)->setModel(Prototype::class, [$value]);
        });

        /*
         * Addressed by number rather than by id, so `/prototypes/{prototype}/versions/3` reads the
         * way a designer says it. A number is only meaningful inside one prototype, which is
         * exactly why the lookup goes through the bound parent — there is no version address that
         * does not carry it.
         */
        Route::bind('prototypeVersion', function (string $value, RouteInstance $route): PrototypeVersion {
            $prototype = $route->parameter('prototype');
            $number = PrototypeVersionNumber::fromRouteSegment($value);

            $version = $prototype instanceof Prototype && $number !== null
                ? $this->app->make(GetPrototypeVersion::class)->handle($prototype, $number)
                : null;

            return $version ?? throw (new ModelNotFoundException)->setModel(PrototypeVersion::class, [$value]);
        });

        Route::bind('artifact', function (string $value, RouteInstance $route): PrototypeArtifact {
            $version = $route->parameter('prototypeVersion');

            $artifact = $version instanceof PrototypeVersion && Str::isUuid($value)
                ? $this->app->make(PrototypeRepository::class)->findArtifactInVersion($version, $value)
                : null;

            return $artifact ?? throw (new ModelNotFoundException)->setModel(PrototypeArtifact::class, [$value]);
        });

        Route::bind('iteration', function (string $value, RouteInstance $route): Iteration {
            $game = $route->parameter('game');

            $iteration = $game instanceof Game && Str::isUuid($value)
                ? $this->app->make(GetIteration::class)->handle($game, $value)
                : null;

            return $iteration ?? throw (new ModelNotFoundException)->setModel(Iteration::class, [$value]);
        });

        Route::bind('change', fn (string $value, RouteInstance $route): DesignChange => $this->resolveInIteration(
            $route,
            $value,
            DesignChange::class,
            fn (IterationRepository $repository, Iteration $iteration): ?DesignChange => $repository
                ->findChangeInIteration($iteration, $value),
        ));

        Route::bind('experiment', fn (string $value, RouteInstance $route): DesignExperiment => $this->resolveInIteration(
            $route,
            $value,
            DesignExperiment::class,
            fn (IterationRepository $repository, Iteration $iteration): ?DesignExperiment => $repository
                ->findExperimentInIteration($iteration, $value),
        ));

        Route::bind('decision', fn (string $value, RouteInstance $route): DesignDecision => $this->resolveInIteration(
            $route,
            $value,
            DesignDecision::class,
            fn (IterationRepository $repository, Iteration $iteration): ?DesignDecision => $repository
                ->findDecisionInIteration($iteration, $value),
        ));

        Route::bind('link', fn (string $value, RouteInstance $route): IterationPlaytest => $this->resolveInIteration(
            $route,
            $value,
            IterationPlaytest::class,
            fn (IterationRepository $repository, Iteration $iteration): ?IterationPlaytest => $repository
                ->findPlaytestLinkInIteration($iteration, $value),
        ));
    }

    /**
     * Resolve one of an iteration's records, or 404.
     *
     * The four child bindings differ only in which lookup they call, so they share this. The uuid
     * check in front of the query is not cosmetic: PostgreSQL raises rather than returning nothing
     * when a uuid column is compared against a string that is not one, which would turn a mistyped
     * URL into a 500.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(IterationRepository, Iteration): ?TModel  $lookup
     * @return TModel
     */
    private function resolveInIteration(RouteInstance $route, string $value, string $model, callable $lookup)
    {
        $iteration = $route->parameter('iteration');

        $resolved = $iteration instanceof Iteration && Str::isUuid($value)
            ? $lookup($this->app->make(IterationRepository::class), $iteration)
            : null;

        return $resolved ?? throw (new ModelNotFoundException)->setModel($model, [$value]);
    }

    /**
     * Point the gate at the module's policies.
     *
     * Two policies for nine models, which is deliberate. A change, an experiment, a decision, a
     * citation and a playtest link have no permissions of their own — what decides whether any of
     * them may be touched is whether the iteration around them is still open, and that is one
     * question with one answer. Giving each its own policy would be five copies of it.
     *
     * The same applies below the prototype: a version and an artifact are governed by the prototype
     * they belong to.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(Prototype::class, PrototypePolicy::class);
        Gate::policy(Iteration::class, IterationPolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch at each call site, so a
     * rule added later surfaces correctly without any controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which is what puts "that is
     * not a prototype version of this game" next to the version picker instead of in a toast — and
     * what puts "this version has already been used, create the next one instead" where the person
     * who just tried to edit v3 will read it.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (IterationRuleViolation $violation, Request $request) {
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

                return back()->withErrors(['iteration' => $violation->getMessage()]);
            });
        });
    }
}
