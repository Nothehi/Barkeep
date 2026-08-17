<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\Commands\ArchiveMechanic;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Presentation\Http\Resources\MechanicResource;

/**
 * Retiring a term, over JSON.
 *
 * A POST to a named action rather than a DELETE, because nothing is deleted —
 * and rather than a status field on the update endpoint, because an
 * irreversible move should not be one field value away from a rename.
 */
class MechanicArchiveController extends Controller
{
    /**
     * Retire it.
     */
    public function store(Request $request, Mechanic $mechanic, ArchiveMechanic $archive): MechanicResource
    {
        Gate::authorize('archive', $mechanic);

        return MechanicResource::make($archive->handle($request->user(), $mechanic));
    }
}
