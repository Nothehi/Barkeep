<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\PrototypeIteration\Application\Commands\CreateNextGameVersion;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateNextGameVersionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Cutting the next design version of a game from what a cycle concluded.
 *
 * Section 48's optional action, and the endpoint where the design loop closes — by a person
 * pressing something, never automatically. Completing an iteration does not cut a version, because
 * most cycles do not produce a new design state and a platform that cut one per cycle would make
 * the version numbers a count of button presses.
 *
 * The response is GameDesign's own resource, because GameDesign created the version: numbered by
 * its allocator, guarded by its rules, announced by its event. This module supplied the occasion
 * and nothing else.
 */
class IterationGameVersionController extends Controller
{
    public function store(
        CreateNextGameVersionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateNextGameVersion $createVersion,
    ): JsonResponse {
        $version = $createVersion->handle(
            $request->user(),
            $iteration,
            $request->versionName(),
            $request->versionDescription(),
        );

        return GameVersionResource::make($version->load('creator'))
            ->response()
            ->setStatusCode(201);
    }
}
