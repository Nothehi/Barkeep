<?php

namespace Modules\DesignFramework\Providers;

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
use Modules\DesignFramework\Application\Queries\GetFramework;
use Modules\DesignFramework\Application\Queries\GetFrameworkVersion;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Queries\GetPhase;
use Modules\DesignFramework\Application\Services\FrameworkContentLocator;
use Modules\DesignFramework\Domain\Exceptions\ContentDoesNotBelongToFrameworkVersion;
use Modules\DesignFramework\Domain\Exceptions\FrameworkRuleViolation;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PhaseContent;
use Modules\DesignFramework\Domain\Policies\FrameworkPolicy;
use Modules\DesignFramework\Domain\Policies\GameFrameworkPolicy;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\GameDesign\Domain\Models\Game;

/**
 * Wires the DesignFramework bounded context into the application.
 *
 * DesignFramework owns the platform's methodology, and everything that decides how a framework is
 * found, who may touch it and how its rules surface over HTTP is configured here rather than being
 * spread across the application's own providers.
 */
class DesignFrameworkServiceProvider extends ServiceProvider
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
     * Two chains meet here, and understanding which one a request is on is the key to the whole
     * arrangement.
     *
     * The **authoring chain** runs framework → version → phase, and content hangs off the version:
     *
     *     /app/frameworks/board-game-design/versions/1/phases/core-loop
     *
     * Each segment resolves *through* the one above it, so a version belonging to another framework
     * or a phase belonging to another version fails to resolve and the request 404s before a
     * handler or a policy runs.
     *
     * The **working chain** runs workspace → game → adoption → content, and the first two segments
     * were already resolved by GameDesign's own binding:
     *
     *     /app/workspaces/prototype-lab/games/bears-and-bridges/framework/criteria/{criterion}
     *
     * Here a criterion is resolved through the framework version the game actually adopted. That is
     * the read-side of section 19's historical integrity: a game on v1 cannot reach v2's criteria at
     * all, so it cannot record an answer to a question it was never asked.
     *
     * ## Why the content bindings are context-aware
     *
     * A criterion is addressed on both chains, and the two want different parents. Rather than
     * giving the same concept two route parameter names — `{criterion}` for the builder and
     * something contrived for the game — one binder looks for whichever parent the URL provides.
     * Both paths resolve *through* a parent; neither ever looks a content id up on its own.
     *
     * That matters because framework content is not secret. A criterion belongs to a globally
     * published version, so an id proves nothing about who is holding it — what keeps one studio out
     * of another's progress is that the adoption comes from the URL's game.
     *
     * The uuid check in front of every content query is not cosmetic: PostgreSQL raises rather than
     * returning nothing when a uuid column is compared against a string that is not one, which would
     * turn a mistyped URL into a 500.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('framework', function (string $value): Framework {
            $framework = FrameworkSlug::isValid($value)
                ? $this->app->make(GetFramework::class)->handle(FrameworkSlug::fromString($value))
                : null;

            return $framework ?? throw (new ModelNotFoundException)->setModel(Framework::class, [$value]);
        });

        /*
         * `{version}` is not this module's name alone. GameDesign binds it too — a game's own
         * iterations live at `/games/bears-and-bridges/versions/2` — and Laravel keeps one binder
         * per parameter name, so the provider registered last would otherwise silently take the
         * name from the one registered first and 404 every game version screen in the product.
         *
         * Renaming the parameter here would fix that and cost the module the word its whole
         * vocabulary is built on, so the binder defers instead: a route with no framework in it is
         * not on the authoring chain, and is handed back to whichever binder held the name before.
         * The handoff is by value rather than by type, so nothing about GameDesign's versions is
         * known here.
         *
         * This is the second thing the provider order in `bootstrap/providers.php` is load-bearing
         * for. DesignFramework has to register after GameDesign, or the delegation runs the wrong
         * way and the framework chain is the one that breaks.
         */
        $previouslyBoundVersion = Route::getBindingCallback('version');

        Route::bind('version', function (string $value, RouteInstance $route) use ($previouslyBoundVersion): mixed {
            $framework = $route->parameter('framework');

            if (! $framework instanceof Framework) {
                return $previouslyBoundVersion === null
                    ? throw (new ModelNotFoundException)->setModel(FrameworkVersion::class, [$value])
                    : $previouslyBoundVersion($value, $route);
            }

            $number = FrameworkVersionNumber::fromRouteSegment($value);

            $version = $number === null
                ? null
                : $this->app->make(GetFrameworkVersion::class)->handle($framework, $number);

            return $version ?? throw (new ModelNotFoundException)->setModel(FrameworkVersion::class, [$value]);
        });

        Route::bind('phase', fn (string $value, RouteInstance $route): DesignPhaseDefinition => $this->resolvePhase($route, $value));

        Route::bind('principle', fn (string $value, RouteInstance $route): PhaseContent => $this->resolveContent(
            $route,
            $value,
            DesignPrinciple::class,
        ));

        Route::bind('criterion', fn (string $value, RouteInstance $route): PhaseContent => $this->resolveContent(
            $route,
            $value,
            DesignCriterion::class,
            fn (FrameworkContentLocator $locator, GameFramework $adoption): PhaseContent => $locator
                ->criterion($adoption, $value),
        ));

        Route::bind('practice', fn (string $value, RouteInstance $route): PhaseContent => $this->resolveContent(
            $route,
            $value,
            DesignPractice::class,
            fn (FrameworkContentLocator $locator, GameFramework $adoption): PhaseContent => $locator
                ->practice($adoption, $value),
        ));

        Route::bind('prompt', fn (string $value, RouteInstance $route): PhaseContent => $this->resolveContent(
            $route,
            $value,
            DesignPrompt::class,
            fn (FrameworkContentLocator $locator, GameFramework $adoption): PhaseContent => $locator
                ->prompt($adoption, $value),
        ));

        Route::bind('checklist', fn (string $value, RouteInstance $route): PhaseContent => $this->resolveContent(
            $route,
            $value,
            Checklist::class,
        ));

        Route::bind('item', fn (string $value, RouteInstance $route): ChecklistItem => $this->resolveChecklistItem($route, $value));
    }

    /**
     * Resolve a phase by address, through whichever parent the URL names.
     *
     * On the authoring chain that parent is the framework version in the URL. On the working chain
     * there is no version segment at all — the game's own adoption supplies it — which is what makes
     * `/games/bears-and-bridges/framework/phases/core-loop` reach v1's core loop for a game on v1,
     * with no id a request could substitute to reach another edition's.
     *
     * A phase that is not published is left for the caller to refuse rather than hidden here, because
     * the two chains want different answers: the builder shows draft phases to their author, and the
     * working screen must not.
     */
    private function resolvePhase(RouteInstance $route, string $value): DesignPhaseDefinition
    {
        if (! ContentSlug::isValid($value)) {
            throw (new ModelNotFoundException)->setModel(DesignPhaseDefinition::class, [$value]);
        }

        $version = $route->parameter('version');

        if (! $version instanceof FrameworkVersion) {
            $version = $this->adoptionFor($route)?->version;
        }

        $phase = $version instanceof FrameworkVersion
            ? $this->app->make(GetPhase::class)->handle($version, ContentSlug::fromString($value))
            : null;

        return $phase ?? throw (new ModelNotFoundException)->setModel(DesignPhaseDefinition::class, [$value]);
    }

    /**
     * Resolve one piece of framework content through whichever parent the URL names.
     *
     * On the authoring chain that parent is the framework version; on the working chain it is the
     * game's adoption, and the lookup goes through `FrameworkContentLocator` so that the version a
     * game is following is the only version its content can come from.
     *
     * Content types with no game-side route — principles and checklists — pass no locator and are
     * therefore reachable only from the builder. That is deliberate rather than an omission: nothing
     * a game records points at either of them.
     *
     * @param  class-string<PhaseContent>  $model
     * @param  (callable(FrameworkContentLocator, GameFramework): PhaseContent)|null  $throughGame
     */
    private function resolveContent(
        RouteInstance $route,
        string $value,
        string $model,
        ?callable $throughGame = null,
    ): PhaseContent {
        if (! Str::isUuid($value)) {
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        $version = $route->parameter('version');

        if ($version instanceof FrameworkVersion) {
            $content = $model::query()
                ->where('framework_version_id', $version->getKey())
                ->whereKey($value)
                ->first();

            return $content instanceof PhaseContent
                ? $content->setRelation('version', $version)
                : throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        if ($throughGame === null) {
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        $adoption = $this->adoptionFor($route);

        if ($adoption === null) {
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        try {
            return $throughGame($this->app->make(FrameworkContentLocator::class), $adoption);
        } catch (ContentDoesNotBelongToFrameworkVersion) {
            /*
             * Reported as "no such thing" rather than as a rule violation. On this chain the id came
             * from a URL somebody typed or followed, and a 404 says everything a 422 would without
             * confirming that the id names content in some other edition.
             */
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }
    }

    /**
     * Resolve a checklist item through its checklist, or through a game's adoption.
     *
     * The one content type whose authoring parent is not a version. An item belongs to a checklist,
     * and its address is unique within that list rather than within the edition.
     */
    private function resolveChecklistItem(RouteInstance $route, string $value): ChecklistItem
    {
        if (! Str::isUuid($value)) {
            throw (new ModelNotFoundException)->setModel(ChecklistItem::class, [$value]);
        }

        $checklist = $route->parameter('checklist');

        if ($checklist instanceof Checklist) {
            $item = $checklist->items()->whereKey($value)->first();

            return $item ?? throw (new ModelNotFoundException)->setModel(ChecklistItem::class, [$value]);
        }

        $adoption = $this->adoptionFor($route);

        if ($adoption === null) {
            throw (new ModelNotFoundException)->setModel(ChecklistItem::class, [$value]);
        }

        try {
            return $this->app->make(FrameworkContentLocator::class)->checklistItem($adoption, $value);
        } catch (ContentDoesNotBelongToFrameworkVersion) {
            throw (new ModelNotFoundException)->setModel(ChecklistItem::class, [$value]);
        }
    }

    /**
     * The framework a route's game follows, if the route names a game at all.
     *
     * The game itself was resolved through a workspace by GameDesign's binding, which is what makes
     * this the last link in a chain rather than a lookup by id.
     */
    private function adoptionFor(RouteInstance $route): ?GameFramework
    {
        $game = $route->parameter('game');

        return $game instanceof Game
            ? $this->app->make(GetGameFramework::class)->handle($game)
            : null;
    }

    /**
     * Point the gate at the module's policies.
     *
     * `FrameworkPolicy` covers both a framework and its versions, because they are two halves of one
     * question — who administers methodology, and is this edition still a draft. Splitting them
     * would mean two files that had to agree about the second half.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(Framework::class, FrameworkPolicy::class);
        Gate::policy(FrameworkVersion::class, FrameworkPolicy::class);
        Gate::policy(GameFramework::class, GameFrameworkPolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch at each call site, so a
     * rule added later surfaces correctly without any controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which is what puts "this
     * framework version has been published and is read-only" next to the form that tried to change
     * it instead of in a toast.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (FrameworkRuleViolation $violation, Request $request) {
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

                return back()->withErrors(['framework' => $violation->getMessage()]);
            });
        });
    }
}
