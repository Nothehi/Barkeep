<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\ArchiveFramework;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkResource;

/**
 * Retiring a methodology.
 *
 * Its own route because it cannot be undone, and because it takes nobody's work with it: games
 * following the framework's versions keep reading them. What it stops is anything new.
 */
class FrameworkArchiveController extends Controller
{
    /**
     * Archive the framework.
     */
    public function store(Request $request, Framework $framework, ArchiveFramework $archiveFramework): FrameworkResource
    {
        Gate::authorize('archive', $framework);

        $archiveFramework->handle($request->user(), $framework);

        return FrameworkResource::make($framework->load('latestVersion')->loadCount('versions'));
    }
}
