<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\CompletePractice;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Queries\GetPracticeCompletions;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Requests\CompletionRequest;
use Modules\DesignFramework\Presentation\Http\Resources\PracticeCompletionResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The framework activities a game has carried out.
 *
 * The practice belongs to the framework; the completion belongs to the game's adoption of it. Section
 * 23 calls that separation critical, and this controller is where it is visible on the wire: the URL
 * names a practice, and what is written is a row against the adoption.
 */
class PracticeCompletionController extends Controller
{
    /**
     * List what this game has done.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        GetPracticeCompletions $getCompletions,
    ): AnonymousResourceCollection {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        return PracticeCompletionResource::collection($getCompletions->handle($adoption));
    }

    /**
     * Mark a practice complete, or take the mark back.
     *
     * Unticking returns 204 rather than a resource, because there is nothing left to represent — the
     * row is gone, which is what makes the state genuinely binary.
     */
    public function store(
        CompletionRequest $request,
        Workspace $workspace,
        Game $game,
        DesignPractice $practice,
        CompletePractice $completePractice,
    ): PracticeCompletionResource|JsonResponse {
        $completion = $completePractice->handle(
            $request->user(),
            $request->adoption(),
            $practice,
            $request->toData(),
        );

        return $completion === null
            ? new JsonResponse(status: 204)
            : PracticeCompletionResource::make($completion);
    }
}
