<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Playtesting\Application\Commands\CreatePlaytestSession;
use Modules\Playtesting\Application\Commands\UpdatePlaytestSession;
use Modules\Playtesting\Application\Queries\GetFeedback;
use Modules\Playtesting\Application\Queries\GetObservations;
use Modules\Playtesting\Application\Queries\GetParticipants;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;
use Modules\Playtesting\Infrastructure\Workspace\WorkspaceRoster;
use Modules\Playtesting\Presentation\Http\Requests\CreateSessionRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdateSessionRequest;
use Modules\Playtesting\Presentation\Http\Resources\FeedbackResource;
use Modules\Playtesting\Presentation\Http\Resources\ObservationResource;
use Modules\Playtesting\Presentation\Http\Resources\ParticipantResource;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestResource;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSessionResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The screens for one sitting of a playtest.
 *
 * The session screen is the one place in the platform somebody uses while
 * something else is happening in the room, so it is rendered with everything
 * it needs in one go: the people, what was noticed and what was said all
 * arrive with the page. Fetching any of them separately would put a spinner
 * between a designer and a thought they are about to forget.
 */
class PlaytestSessionController extends Controller
{
    /**
     * Schedule another sitting and go to it.
     *
     * Landing on the session rather than returning to the playtest is the
     * point: the reason somebody creates a session is almost always that they
     * are about to run one.
     */
    public function store(
        CreateSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        CreatePlaytestSession $createSession,
    ): RedirectResponse {
        $session = $createSession->handle($request->user(), $playtest, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session scheduled.')]);

        return to_route('playtests.sessions.show', [$workspace, $game, $playtest, $session]);
    }

    /**
     * Show a session and everything recorded in it.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        GetParticipants $getParticipants,
        GetObservations $getObservations,
        GetFeedback $getFeedback,
        WorkspaceRoster $roster,
    ): Response {
        Gate::authorize('view', $session);

        return Inertia::render('playtests/session', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'playtest' => PlaytestResource::make($playtest->load(['version', 'creator'])),
            'session' => PlaytestSessionResource::make($session->load('creator')),
            'participants' => ParticipantResource::collection($getParticipants->handle($session)),
            'observations' => ObservationResource::collection($getObservations->handle($session)),
            'feedback' => FeedbackResource::collection($getFeedback->handle($session)),

            /*
             * The team, so the "add participant" form can offer people by name
             * instead of asking somebody to paste an identifier. Anyone not on
             * this list is still welcome at the session — they are recorded as
             * a guest, which is what most participants are.
             */
            'teammates' => UserResource::collection($roster->candidatesFor($game)),
            'options' => $this->options(),
        ]);
    }

    /**
     * Change a session that has not ended.
     */
    public function update(
        UpdateSessionRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        UpdatePlaytestSession $updateSession,
    ): RedirectResponse {
        $updateSession->handle($request->user(), $session, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session updated.')]);

        return back();
    }

    /**
     * The values the session screen lets somebody choose between.
     *
     * @return array{categories: list<array{value: string, label: string, description: string}>, roles: list<array{value: string, label: string, description: string}>, rating_scale: list<int>}
     */
    private function options(): array
    {
        return [
            'categories' => array_map(
                fn (ObservationCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                    'description' => $category->description(),
                ],
                ObservationCategory::cases(),
            ),
            'roles' => array_map(
                fn (PlaytestParticipantRole $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                ],
                PlaytestParticipantRole::cases(),
            ),
            'rating_scale' => FeedbackRating::scale(),
        ];
    }
}
