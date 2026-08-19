<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CancelIteration;
use Modules\PrototypeIteration\Application\Commands\CompleteIteration;
use Modules\PrototypeIteration\Application\Commands\StartIteration;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CancelIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\CompleteIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\StartIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a design cycle through its life.
 *
 * Three POSTs to named actions rather than a PATCH of a status field, because each is an action
 * with rules — completing one requires an outcome and a summary — rather than an editable
 * attribute. It is also what keeps the lifecycle matrix in the domain: a client asks for a move
 * and the server decides whether it is legal, instead of the client choosing a value.
 */
class IterationLifecycleController extends Controller
{
    /**
     * Begin the work.
     */
    public function start(
        StartIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        StartIteration $startIteration,
    ): IterationResource {
        $startIteration->handle($request->user(), $iteration);

        return IterationResource::make($this->rendered($iteration));
    }

    /**
     * Close the cycle with an outcome and a summary.
     */
    public function complete(
        CompleteIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CompleteIteration $completeIteration,
    ): IterationResource {
        $completeIteration->handle($request->user(), $iteration, $request->toData());

        return IterationResource::make($this->rendered($iteration));
    }

    /**
     * Call the cycle off.
     */
    public function cancel(
        CancelIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CancelIteration $cancelIteration,
    ): IterationResource {
        $cancelIteration->handle($request->user(), $iteration);

        return IterationResource::make($this->rendered($iteration));
    }

    /**
     * Load what the full representation of a cycle needs.
     */
    private function rendered(Iteration $iteration): Iteration
    {
        return $iteration
            ->load(['version', 'prototypeVersion.prototype', 'creator'])
            ->loadCount(['changes', 'experiments', 'decisions', 'playtestLinks']);
    }
}
