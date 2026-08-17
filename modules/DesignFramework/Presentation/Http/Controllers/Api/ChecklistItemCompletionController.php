<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\CompleteChecklistItem;
use Modules\DesignFramework\Application\Queries\GetChecklistProgress;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Requests\CompletionRequest;
use Modules\DesignFramework\Presentation\Http\Resources\ChecklistItemCompletionResource;
use Modules\DesignFramework\Presentation\Http\Resources\ChecklistProgressResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's progress through its framework's checklists.
 *
 * The list endpoint returns checklists paired with ticks rather than a list of completions, because
 * that is how a checklist is read: "2 of 4" above four boxes, not two rows saying something was
 * done. The write endpoint is per item.
 */
class ChecklistItemCompletionController extends Controller
{
    /**
     * List the game's checklists and which requirements it has met.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        GetChecklistProgress $getProgress,
    ): AnonymousResourceCollection {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        return ChecklistProgressResource::collection($getProgress->handle($adoption));
    }

    /**
     * Tick a requirement, or untick it.
     */
    public function store(
        CompletionRequest $request,
        Workspace $workspace,
        Game $game,
        ChecklistItem $item,
        CompleteChecklistItem $completeItem,
    ): ChecklistItemCompletionResource|JsonResponse {
        $completion = $completeItem->handle(
            $request->user(),
            $request->adoption(),
            $item,
            $request->toData(),
        );

        return $completion === null
            ? new JsonResponse(status: 204)
            : ChecklistItemCompletionResource::make($completion);
    }
}
