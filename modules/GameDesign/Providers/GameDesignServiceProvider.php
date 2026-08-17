<?php

namespace Modules\GameDesign\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Modules\GameDesign\Application\Queries\GetGame;
use Modules\GameDesign\Application\Queries\GetGameVersion;
use Modules\GameDesign\Application\Queries\GetMechanic;
use Modules\GameDesign\Domain\Exceptions\GameRuleViolation;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Domain\Policies\GamePolicy;
use Modules\GameDesign\Domain\Policies\MechanicPolicy;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;
use Modules\GameDesign\Domain\ValueObjects\MechanicSlug;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;
use Modules\Workspace\Application\Queries\GetWorkspace;
use Modules\Workspace\Domain\Exceptions\InvalidWorkspaceSlug;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

/**
 * Wires the GameDesign bounded context into the application.
 *
 * GameDesign owns the product's core aggregate, and everything that decides
 * how a game is found, who may touch it and how its rules surface over HTTP
 * is configured here rather than being spread across the application's own
 * providers.
 */
class GameDesignServiceProvider extends ServiceProvider
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
     * Teach the router how to find a game and a version.
     *
     * Games are addressed by a slug that is only unique inside a workspace,
     * so resolving one means resolving it *through* a workspace. Laravel's
     * scoped implicit bindings would do that by calling a `games()` relation
     * on the workspace model — which would mean Workspace holding a reference
     * to GameDesign, inverting the dependency the architecture depends on and
     * breaking Workspace's own architecture tests.
     *
     * Binding explicitly avoids that entirely. GameDesign reaches down into
     * Workspace, Workspace stays unaware of GameDesign, and every game in the
     * application is resolved by the one workspace-scoped query in the
     * module's own query layer.
     *
     * The security property this buys is worth stating plainly: there is no
     * route on which a game id or address is looked up without a workspace.
     * A game belonging to somebody else's workspace does not 403 — it fails
     * to resolve, and the request 404s before any handler sees it.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('game', function (string $value, RouteInstance $route): Game {
            $workspace = $this->workspaceFor($route);

            $game = GameSlug::isValid($value)
                ? $this->app->make(GetGame::class)->handle($workspace, GameSlug::fromString($value))
                : null;

            return $game ?? throw (new ModelNotFoundException)->setModel(Game::class, [$value]);
        });

        Route::bind('version', function (string $value, RouteInstance $route): GameVersion {
            $game = $route->parameter('game');

            $number = VersionNumber::fromRouteSegment($value);

            $version = $game instanceof Game && $number !== null
                ? $this->app->make(GetGameVersion::class)->handle($game, $number)
                : null;

            return $version ?? throw (new ModelNotFoundException)->setModel(GameVersion::class, [$value]);
        });

        /*
         * The one binding in this module that resolves through nothing. A
         * mechanic is the platform's, so there is no parent segment to scope it
         * by and no tenancy to enforce — which is exactly why it is worth
         * flagging here rather than letting it read as an oversight.
         *
         * `{mechanic}` is checked against the other modules' parameter names
         * before being claimed; see `.ai/rules/providers.md`, which records what
         * happens when two providers bind the same one.
         */
        Route::bind('mechanic', function (string $value): Mechanic {
            $mechanic = MechanicSlug::isValid($value)
                ? $this->app->make(GetMechanic::class)->handle(MechanicSlug::fromString($value))
                : null;

            return $mechanic ?? throw (new ModelNotFoundException)->setModel(Mechanic::class, [$value]);
        });
    }

    /**
     * Resolve the workspace a game route is nested under.
     *
     * Explicit bindings are substituted before implicit ones, so by the time
     * this runs the `{workspace}` parameter is usually still the raw slug
     * from the URL. It is resolved here, through Workspace's own published
     * query, and written back onto the route.
     *
     * Writing it back is what keeps there being exactly one workspace object
     * for the request: the implicit binding that follows skips a parameter
     * that already holds a model, and the controller, the policy and the
     * game's own `workspace` relation all end up sharing the instance — and
     * therefore its memoised membership lookup.
     */
    private function workspaceFor(RouteInstance $route): Workspace
    {
        $parameter = $route->parameter('workspace');

        if ($parameter instanceof Workspace) {
            return $parameter;
        }

        if (! is_string($parameter)) {
            throw (new ModelNotFoundException)->setModel(Workspace::class);
        }

        try {
            $slug = WorkspaceSlug::fromString($parameter);
        } catch (InvalidWorkspaceSlug) {
            throw (new ModelNotFoundException)->setModel(Workspace::class, [$parameter]);
        }

        $workspace = $this->app->make(GetWorkspace::class)->handle($slug)
            ?? throw (new ModelNotFoundException)->setModel(Workspace::class, [$slug->value]);

        $route->setParameter('workspace', $workspace);

        return $workspace;
    }

    /**
     * Point the gate at the module's policies.
     *
     * Two, because there are two kinds of thing here. A game is a studio's and
     * is authorized through its workspace; the mechanics vocabulary is the
     * platform's and is authorized by a configured list of curators. Folding
     * them into one policy would put a workspace role within reach of a
     * decision that must never depend on one.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(Game::class, GamePolicy::class);
        Gate::policy(Mechanic::class, MechanicPolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch
     * at each call site, so a rule added later surfaces correctly without any
     * controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which
     * is what puts "this workspace already has a game there" next to the
     * address input instead of in a toast.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (GameRuleViolation $violation, Request $request) {
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

                return back()->withErrors(['game' => $violation->getMessage()]);
            });
        });
    }
}
