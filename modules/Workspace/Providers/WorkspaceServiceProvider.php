<?php

namespace Modules\Workspace\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\Queries\GetUserWorkspaces;
use Modules\Workspace\Domain\Exceptions\WorkspaceRuleViolation;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Policies\WorkspacePolicy;
use Modules\Workspace\Infrastructure\Http\Middleware\EnsureWorkspaceIsSelected;
use Modules\Workspace\Infrastructure\Session\ActiveWorkspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * Wires the Workspace bounded context into the application.
 *
 * Workspace owns the tenancy boundary, so everything that decides who may see
 * or change a workspace is configured here rather than being spread across
 * the application's own providers.
 */
class WorkspaceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module.
     */
    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configureMiddleware();
        $this->configureExceptionRendering();
        $this->configureSharedData();
    }

    /**
     * Publish the module's middleware under a name routes can ask for.
     *
     * Registered here rather than in the application's HTTP kernel so that a
     * route file only has to know the rule it wants — "a workspace has been
     * chosen" — and nothing about how Workspace remembers the choice.
     */
    private function configureMiddleware(): void
    {
        $this->callAfterResolving(Router::class, function (Router $router): void {
            $router->aliasMiddleware('workspace.selected', EnsureWorkspaceIsSelected::class);
        });
    }

    /**
     * Point the gate at the module's policy.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch
     * at each call site, so a rule added later surfaces correctly without any
     * controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which
     * is what puts "that address is already taken" next to the address input
     * instead of in a toast.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (WorkspaceRuleViolation $violation, Request $request) {
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

                return back()->withErrors(['workspace' => $violation->getMessage()]);
            });
        });
    }

    /**
     * Share the caller's workspaces with every Inertia page.
     *
     * The switcher needs this on every screen, and Workspace contributes it
     * itself so no layout has to know how membership is stored. The list is
     * scoped to membership, so it can only ever offer workspaces the account
     * actually belongs to.
     *
     * This is navigation data. Which workspace the client thinks is selected
     * carries no authority — every request is authorized against the
     * workspace the URL actually resolves to.
     */
    private function configureSharedData(): void
    {
        Inertia::share('workspaces', function (Request $request): ?array {
            $user = $request->user();

            if (! $user instanceof User) {
                return null;
            }

            $workspaces = $this->app->make(GetUserWorkspaces::class)->handle($user);

            return [
                'available' => WorkspaceResource::collection($workspaces)->resolve($request),
                'current' => $this->currentWorkspaceSlug($request, $workspaces),
            ];
        });
    }

    /**
     * The workspace the page is about.
     *
     * A URL that names a workspace answers this on its own, and its answer
     * wins: the screen being served is about that workspace whatever was
     * chosen earlier. Everywhere else — the dashboard, the platform-wide
     * catalogues — it is the workspace the account chose after signing in.
     *
     * The chosen address is only reported back if it is still in the list the
     * account may switch between, so a workspace somebody has left stops
     * being named the moment the membership ends.
     *
     * @param  Collection<int, Workspace>  $workspaces
     */
    private function currentWorkspaceSlug(Request $request, Collection $workspaces): ?string
    {
        $workspace = $request->route()?->parameter('workspace');

        if ($workspace instanceof Workspace) {
            return $workspace->slug;
        }

        $chosen = $this->app->make(ActiveWorkspace::class)->slug();

        return $workspaces->contains(fn (Workspace $available) => $available->slug === $chosen)
            ? $chosen
            : null;
    }
}
