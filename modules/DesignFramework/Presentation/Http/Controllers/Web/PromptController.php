<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CreatePrompt;
use Modules\DesignFramework\Application\Commands\ReorderPrompt;
use Modules\DesignFramework\Application\Commands\UpdatePrompt;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Requests\Content\CreatePromptRequest;
use Modules\DesignFramework\Presentation\Http\Requests\Content\UpdatePromptRequest;
use Modules\DesignFramework\Presentation\Http\Requests\ReorderRequest;

/**
 * The framework builder's thinking questions.
 *
 * Three actions, all of them writes, all of them refused on a published edition by the one ability
 * the whole builder is arranged around. Nothing here renders a screen: the builder is a single page
 * served by `FrameworkVersionController`, and every write comes back to it as a redirect so the
 * reloaded page is what the server actually stored rather than something the client spliced in.
 */
class PromptController extends Controller
{
    /**
     * Add one to the edition.
     */
    public function store(
        CreatePromptRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        CreatePrompt $create,
    ): RedirectResponse {
        $create->handle($request->user(), $version, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added.')]);

        return back();
    }

    /**
     * Change one.
     */
    public function update(
        UpdatePromptRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        DesignPrompt $prompt,
        UpdatePrompt $update,
    ): RedirectResponse {
        $update->handle($request->user(), $prompt, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    /**
     * Move one among its siblings.
     *
     * A POST rather than a PATCH of a position field, because the position is not an attribute a
     * client sets — it is allocated by `ContentSequencer`, which rewrites the whole sibling set so the
     * result is always contiguous.
     */
    public function reorder(
        ReorderRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        DesignPrompt $prompt,
        ReorderPrompt $reorder,
    ): RedirectResponse {
        $reorder->handle($request->user(), $prompt, $request->position());

        return back();
    }
}
