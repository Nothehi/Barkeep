<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Modules\PrototypeIteration\Application\DTOs\WorkspaceIterationActivity;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What this module can tell the app's home screen about a workspace.
 *
 * Every other query here is scoped to one game, because that is how prototypes
 * and cycles are read: from the project they belong to. This one is scoped to
 * the workspace instead, for the one screen that is about a studio rather than
 * about a design — and the scope is still a required argument, so there is no
 * "every iteration in the platform" query to reach for by mistake.
 *
 * It is the only place the two repositories are asked a question together,
 * which is the point of it being a query rather than three calls from a
 * controller: what "the state of the workshop" consists of is decided here.
 *
 * Resolution is unauthorized on purpose: gathering the figures and deciding
 * who may read them are separate steps, and the caller runs the policy against
 * the workspace first.
 */
final class GetWorkspaceIterationActivity
{
    public function __construct(
        private readonly PrototypeRepository $prototypes,
        private readonly IterationRepository $iterations,
    ) {}

    public function handle(Workspace $workspace): WorkspaceIterationActivity
    {
        return new WorkspaceIterationActivity(
            prototypeCount: $this->prototypes->countForWorkspace($workspace),
            iterationCount: $this->iterations->countForWorkspace($workspace),
            openIterationCount: $this->iterations->openCountForWorkspace($workspace),
        );
    }
}
