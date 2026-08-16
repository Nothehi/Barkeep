<?php

namespace Modules\Playtesting\Providers;

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
use Modules\Playtesting\Application\Queries\GetPlaytest;
use Modules\Playtesting\Application\Queries\GetSession;
use Modules\Playtesting\Domain\Exceptions\PlaytestRuleViolation;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Domain\Policies\PlaytestPolicy;
use Modules\Playtesting\Domain\Policies\PlaytestSessionPolicy;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * Wires the Playtesting bounded context into the application.
 *
 * Playtesting owns the platform's evidence, and everything that decides how a
 * playtest is found, who may touch it and how its rules surface over HTTP is
 * configured here rather than being spread across the application's own
 * providers.
 */
class PlaytestingServiceProvider extends ServiceProvider
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
     * Five bindings, each resolving a record *through* the one above it:
     * a playtest through its game, a session through its playtest, and
     * participants, observations and feedback through their session. The game
     * itself was already resolved through a workspace by GameDesign's own
     * binding, so the whole ownership chain —
     *
     *     workspace → game → playtest → session → evidence
     *
     * — is walked by the router before any handler or policy runs.
     *
     * The security property that buys is worth stating plainly. There is no
     * route in this module on which an identifier is looked up without its
     * parent. A session belonging to somebody else's playtest does not 403; it
     * fails to resolve, and the request 404s before a controller sees it. That
     * is why the module's ids can be opaque uuids in a URL without any of them
     * being a capability.
     *
     * Explicit bindings run in the order the parameters appear in the URL, and
     * the URLs are nested in the same order as the chain — which is what makes
     * "the parent is already a model by the time the child resolves" true
     * rather than hopeful.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('playtest', function (string $value, RouteInstance $route): Playtest {
            $game = $route->parameter('game');

            $playtest = $game instanceof Game && Str::isUuid($value)
                ? $this->app->make(GetPlaytest::class)->handle($game, $value)
                : null;

            return $playtest ?? throw (new ModelNotFoundException)->setModel(Playtest::class, [$value]);
        });

        Route::bind('session', function (string $value, RouteInstance $route): PlaytestSession {
            $playtest = $route->parameter('playtest');

            $session = $playtest instanceof Playtest && Str::isUuid($value)
                ? $this->app->make(GetSession::class)->handle($playtest, $value)
                : null;

            return $session ?? throw (new ModelNotFoundException)->setModel(PlaytestSession::class, [$value]);
        });

        Route::bind('participant', fn (string $value, RouteInstance $route): PlaytestParticipant => $this->resolveInSession(
            $route,
            $value,
            PlaytestParticipant::class,
            fn (PlaytestRepository $repository, PlaytestSession $session): ?PlaytestParticipant => $repository
                ->findParticipantInSession($session, $value),
        ));

        Route::bind('observation', fn (string $value, RouteInstance $route): PlaytestObservation => $this->resolveInSession(
            $route,
            $value,
            PlaytestObservation::class,
            fn (PlaytestRepository $repository, PlaytestSession $session): ?PlaytestObservation => $repository
                ->findObservationInSession($session, $value),
        ));

        Route::bind('feedback', fn (string $value, RouteInstance $route): PlaytestFeedback => $this->resolveInSession(
            $route,
            $value,
            PlaytestFeedback::class,
            fn (PlaytestRepository $repository, PlaytestSession $session): ?PlaytestFeedback => $repository
                ->findFeedbackInSession($session, $value),
        ));
    }

    /**
     * Resolve one of a session's records, or 404.
     *
     * The three evidence bindings differ only in which lookup they call, so
     * they share this. The uuid check in front of the query is not cosmetic:
     * PostgreSQL raises rather than returning nothing when a uuid column is
     * compared against a string that is not one, which would turn a mistyped
     * URL into a 500.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(PlaytestRepository, PlaytestSession): ?TModel  $lookup
     * @return TModel
     */
    private function resolveInSession(RouteInstance $route, string $value, string $model, callable $lookup)
    {
        $session = $route->parameter('session');

        $resolved = $session instanceof PlaytestSession && Str::isUuid($value)
            ? $lookup($this->app->make(PlaytestRepository::class), $session)
            : null;

        return $resolved ?? throw (new ModelNotFoundException)->setModel($model, [$value]);
    }

    /**
     * Point the gate at the module's policies.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(Playtest::class, PlaytestPolicy::class);
        Gate::policy(PlaytestSession::class, PlaytestSessionPolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch
     * at each call site, so a rule added later surfaces correctly without any
     * controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which
     * is what puts "that version belongs to a different game" next to the
     * version picker instead of in a toast.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (PlaytestRuleViolation $violation, Request $request) {
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

                return back()->withErrors(['playtest' => $violation->getMessage()]);
            });
        });
    }
}
