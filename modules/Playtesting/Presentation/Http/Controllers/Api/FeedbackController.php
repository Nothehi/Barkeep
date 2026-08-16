<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Commands\CreateFeedback;
use Modules\Playtesting\Application\Commands\DeleteFeedback;
use Modules\Playtesting\Application\Commands\UpdateFeedback;
use Modules\Playtesting\Application\Queries\GetFeedback;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Presentation\Http\Requests\CreateFeedbackRequest;
use Modules\Playtesting\Presentation\Http\Requests\DeleteFeedbackRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdateFeedbackRequest;
use Modules\Playtesting\Presentation\Http\Resources\FeedbackResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What participants said about a session.
 *
 * A separate endpoint from the observations rather than a filter on them. The
 * screen interleaves the two into one timeline, but "somebody noticed" and
 * "somebody said" have to stay distinguishable as far as the reader, and
 * merging them at the API would lose the distinction before it got there.
 */
class FeedbackController extends Controller
{
    /**
     * List what participants said, oldest first.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        GetFeedback $getFeedback,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $session);

        return FeedbackResource::collection($getFeedback->handle($session));
    }

    /**
     * Record what a participant said.
     */
    public function store(
        CreateFeedbackRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        CreateFeedback $createFeedback,
    ): JsonResponse {
        $feedback = $createFeedback->handle($request->user(), $session, $request->toData());

        return FeedbackResource::make($feedback->load(['participant', 'creator']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Correct a piece of feedback while the session is still open.
     */
    public function update(
        UpdateFeedbackRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestFeedback $feedback,
        UpdateFeedback $updateFeedback,
    ): FeedbackResource {
        $updateFeedback->handle($request->user(), $session, $feedback, $request->toData());

        return FeedbackResource::make($feedback->load(['participant', 'creator']));
    }

    /**
     * Withdraw a piece of feedback while the session is still open.
     */
    public function destroy(
        DeleteFeedbackRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        PlaytestSession $session,
        PlaytestFeedback $feedback,
        DeleteFeedback $deleteFeedback,
    ): JsonResponse {
        $deleteFeedback->handle($request->user(), $session, $feedback);

        return response()->json(status: 204);
    }
}
