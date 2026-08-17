<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CreateChecklistItem;
use Modules\DesignFramework\Application\Commands\ReorderChecklistItem;
use Modules\DesignFramework\Application\Commands\UpdateChecklistItem;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Requests\CreateChecklistItemRequest;
use Modules\DesignFramework\Presentation\Http\Requests\ReorderRequest;
use Modules\DesignFramework\Presentation\Http\Requests\UpdateChecklistItemRequest;

/**
 * The framework builder's checklist requirements.
 *
 * Nested a level deeper than the rest of the builder, because an item belongs to a checklist rather
 * than to a version — and is resolved through it, so an item from another list fails to resolve rather
 * than being caught by a policy.
 */
class ChecklistItemController extends Controller
{
    /**
     * Add a requirement to a checklist.
     */
    public function store(
        CreateChecklistItemRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        Checklist $checklist,
        CreateChecklistItem $createItem,
    ): RedirectResponse {
        $createItem->handle($request->user(), $checklist, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item added.')]);

        return back();
    }

    /**
     * Change a requirement.
     */
    public function update(
        UpdateChecklistItemRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        Checklist $checklist,
        ChecklistItem $item,
        UpdateChecklistItem $updateItem,
    ): RedirectResponse {
        $updateItem->handle($request->user(), $item, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item updated.')]);

        return back();
    }

    /**
     * Move a requirement within its list.
     */
    public function reorder(
        ReorderRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        Checklist $checklist,
        ChecklistItem $item,
        ReorderChecklistItem $reorderItem,
    ): RedirectResponse {
        $reorderItem->handle($request->user(), $item, $request->position());

        return back();
    }
}
