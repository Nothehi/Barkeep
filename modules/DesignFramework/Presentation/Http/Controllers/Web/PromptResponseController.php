<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\RespondToPrompt;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Presentation\Http\Requests\RespondToPromptRequest;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Writing a game's answer to one of the framework's questions.

 * The most valuable write in the module. "What is the most interesting decision in your game?" is a
 * question a designer can only answer by thinking, and the answer is what they reread when the design
 * has drifted.
 *
 * The prompt was resolved through the framework version this game adopted, so one from another
 * edition 404s before this runs. The write comes back as a redirect, so the reloaded phase page shows
 * what the server actually stored rather than something the client spliced in — which matters on a
 * screen a designer edits repeatedly while thinking.
 */
class PromptResponseController extends Controller
{
    /**
     * Record it.
     */
    public function store(
        RespondToPromptRequest $request,
        Workspace $workspace,
        Game $game,
        DesignPrompt $prompt,
        RespondToPrompt $command,
    ): RedirectResponse {
        $command->handle($request->user(), $request->adoption(), $prompt, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Response saved.')]);

        return back();
    }
}
