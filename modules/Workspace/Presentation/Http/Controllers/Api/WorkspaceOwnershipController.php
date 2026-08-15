<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Workspace\Application\Commands\TransferWorkspaceOwnership;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Requests\TransferOwnershipRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * Handing a workspace to somebody else.
 *
 * Ownership has its own endpoint rather than travelling as a role update,
 * because it is the one change that has to move two membership rows and the
 * workspace itself at once.
 */
class WorkspaceOwnershipController extends Controller
{
    /**
     * Transfer ownership to another member.
     */
    public function store(
        TransferOwnershipRequest $request,
        Workspace $workspace,
        TransferWorkspaceOwnership $transferOwnership,
    ): WorkspaceResource {
        $transferOwnership->handle(
            $request->user(),
            $workspace,
            $request->newOwner(),
            $request->outgoingOwnerRole(),
        );

        return WorkspaceResource::make($workspace->loadCount('members'));
    }
}
