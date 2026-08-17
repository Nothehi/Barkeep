<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\RespondToPrompt;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Queries\GetPromptResponses;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Requests\RespondToPromptRequest;
use Modules\DesignFramework\Presentation\Http\Resources\PromptResponseResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What a game's designers have written in answer to the framework's questions.
 *
 * The most sensitive read in the module: these paragraphs are a studio's design thinking, which is why
 * they are only ever reachable through that studio's own game and never listed across games.
 */
class PromptResponseController extends Controller
{
    /**
     * List this game's answers.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        GetPromptResponses $getResponses,
    ): AnonymousResourceCollection {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        return PromptResponseResource::collection($getResponses->handle($adoption));
    }

    /**
     * Answer one of the framework's questions.
     *
     * Answering again overwrites, because a prompt asks what the design is now.
     */
    public function store(
        RespondToPromptRequest $request,
        Workspace $workspace,
        Game $game,
        DesignPrompt $prompt,
        RespondToPrompt $respondToPrompt,
    ): PromptResponseResource {
        $response = $respondToPrompt->handle(
            $request->user(),
            $request->adoption(),
            $prompt,
            $request->toData(),
        );

        return PromptResponseResource::make($response);
    }
}
