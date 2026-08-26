<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Application\Queries\GetWorkspaceDesignActivity;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Infrastructure\Authorization\GamePermissions;
use Modules\GameDesign\Presentation\Http\Resources\GameSummaryResource;
use Modules\Playtesting\Application\Queries\GetWorkspacePlaytestActivity;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Presentation\Http\Resources\WorkspacePlaytestResource;
use Modules\PrototypeIteration\Application\Queries\GetWorkspaceIterationActivity;
use Modules\Workspace\Application\Queries\GetUserWorkspaces;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Infrastructure\Session\ActiveWorkspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The app's own home, and so the screen every sign in lands on.
 *
 * It lives in the application rather than in a module because it is the one
 * screen that is nobody's: it reads GameDesign, Playtesting and
 * PrototypeIteration together, and putting it inside any of them would point a
 * dependency the wrong way — Workspace in particular is not allowed to learn
 * that the contexts above it exist, and the architecture tests hold that line.
 *
 * Every context is asked one question through its own published query, so this
 * controller composes answers without knowing how any of them are stored. What
 * a workspace's overview *consists of* is decided here, which is the only
 * cross-context decision the file makes.
 *
 * Notably absent: rule sets and balance profiles. Both belong to a version of
 * a game rather than to a studio, so "eleven rule sets" is a number with no
 * question behind it — and a dashboard of figures nobody asked for is worse
 * than a smaller one that answers something. The same restraint the game
 * overview shows about invented metrics applies here.
 */
class DashboardController extends Controller
{
    /**
     * Show what is going on in the workspace being worked in.
     *
     * The workspace comes from the session rather than from the URL, because
     * this is the one screen inside a workspace whose address does not name
     * one — see `EnsureWorkspaceIsSelected`, which has already established
     * that a choice was made and that it is still a workspace this account
     * belongs to. The policy runs again here anyway: the middleware answers
     * "has a workspace been chosen", not "may this be read".
     */
    public function show(
        Request $request,
        ActiveWorkspace $activeWorkspace,
        GetUserWorkspaces $getUserWorkspaces,
        GetWorkspaceDesignActivity $getDesignActivity,
        GetWorkspacePlaytestActivity $getPlaytestActivity,
        GetWorkspaceIterationActivity $getIterationActivity,
    ): Response|RedirectResponse {
        $workspace = $this->chosenWorkspace($request, $activeWorkspace, $getUserWorkspaces);

        if ($workspace === null) {
            return to_route('workspaces.select');
        }

        Gate::authorize('view', $workspace);

        $design = $getDesignActivity->handle($workspace);
        $playtesting = $getPlaytestActivity->handle($workspace);
        $iteration = $getIterationActivity->handle($workspace);

        return Inertia::render('dashboard', [
            'workspace' => WorkspaceResource::make($workspace),

            'games' => [
                'total' => $design->gameCount,
                'versions_count' => $design->versionCount,
                'by_status' => $this->statusDistribution($design->gamesByStatus),
                'by_design_phase' => $this->phaseDistribution($design->gamesByDesignPhase),
                'recent' => GameSummaryResource::collection($design->recentGames),
            ],

            'playtesting' => [
                'total' => $playtesting->playtestCount,
                'sessions_count' => $playtesting->sessionCount,
                'by_status' => $this->playtestStatusDistribution($playtesting->playtestsByStatus),
                'recent' => WorkspacePlaytestResource::collection($playtesting->recentPlaytests),
            ],

            'iteration' => [
                'prototypes_count' => $iteration->prototypeCount,
                'iterations_count' => $iteration->iterationCount,
                'open_iterations_count' => $iteration->openIterationCount,
            ],

            'can' => [
                'createGame' => app(GamePermissions::class)->canCreateIn($request->user(), $workspace),
            ],
        ]);
    }

    /**
     * The workspace the account chose after signing in.
     *
     * Re-checked against membership rather than trusted, in the same way and
     * for the same reason the middleware does it: the stored address carries no
     * authority. Null when the choice no longer holds, which sends the request
     * back to the chooser instead of rendering somebody else's studio.
     */
    private function chosenWorkspace(
        Request $request,
        ActiveWorkspace $activeWorkspace,
        GetUserWorkspaces $getUserWorkspaces,
    ): ?Workspace {
        $slug = $activeWorkspace->slug();

        if ($slug === null) {
            return null;
        }

        return $getUserWorkspaces->handle($request->user())
            ->first(fn (Workspace $workspace): bool => $workspace->slug === $slug);
    }

    /**
     * A game-status tally, worded and ordered for the screen.
     *
     * The labels and the order come from the enum rather than being restated
     * in TypeScript, which is the same arrangement every other screen uses: a
     * client that kept its own copy would be a second opinion waiting to go
     * stale.
     *
     * @param  array<string, int>  $tally
     * @return list<array{value: string, label: string, count: int}>
     */
    private function statusDistribution(array $tally): array
    {
        return array_map(
            fn (GameStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => $tally[$status->value] ?? 0,
            ],
            GameStatus::cases(),
        );
    }

    /**
     * A design-phase tally, worded and ordered for the screen.
     *
     * The position comes along so progress can be drawn without the client
     * knowing the order of the phases — the same reason `GameSummaryResource`
     * sends it.
     *
     * @param  array<string, int>  $tally
     * @return list<array{value: string, label: string, position: int, count: int}>
     */
    private function phaseDistribution(array $tally): array
    {
        return array_map(
            fn (DesignPhase $phase): array => [
                'value' => $phase->value,
                'label' => $phase->label(),
                'position' => $phase->position(),
                'count' => $tally[$phase->value] ?? 0,
            ],
            DesignPhase::cases(),
        );
    }

    /**
     * A playtest-status tally, worded and ordered for the screen.
     *
     * @param  array<string, int>  $tally
     * @return list<array{value: string, label: string, count: int}>
     */
    private function playtestStatusDistribution(array $tally): array
    {
        return array_map(
            fn (PlaytestStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => $tally[$status->value] ?? 0,
            ],
            PlaytestStatus::cases(),
        );
    }
}
