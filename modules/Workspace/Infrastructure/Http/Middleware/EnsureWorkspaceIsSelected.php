<?php

namespace Modules\Workspace\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\Queries\GetUserWorkspaces;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Infrastructure\Session\ActiveWorkspace;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a workspace to have been chosen before the app's own home opens.
 *
 * Signing in leaves an account somewhere ambiguous when it belongs to more
 * than one workspace, so the first screen after it asks which one. The check
 * lives in middleware rather than in a login redirect because a session
 * outlives the sign in that started it: the choice has to hold up on every
 * later visit too, including one made straight to the URL.
 *
 * The stored choice is re-checked against membership each time, so leaving a
 * workspace — or being removed from it — sends the account back to the
 * chooser instead of leaving it pointed somewhere it no longer belongs.
 */
class EnsureWorkspaceIsSelected
{
    public function __construct(
        private readonly ActiveWorkspace $activeWorkspace,
        private readonly GetUserWorkspaces $userWorkspaces,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($this->choiceStillHolds($user)) {
            return $next($request);
        }

        $this->activeWorkspace->forget();

        return redirect()->route('workspaces.select');
    }

    /**
     * Determine whether the chosen address is still one the account may open.
     */
    private function choiceStillHolds(User $user): bool
    {
        $slug = $this->activeWorkspace->slug();

        if ($slug === null) {
            return false;
        }

        return $this->userWorkspaces->handle($user)
            ->contains(fn (Workspace $workspace) => $workspace->slug === $slug);
    }
}
