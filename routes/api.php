<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceArchiveController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceInvitationController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceLeaveController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceMemberController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceMemberInvitationController;
use Modules\Workspace\Presentation\Http\Controllers\Api\WorkspaceOwnershipController;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| A first-party JSON surface over the same application commands the Inertia
| screens use. It is session authenticated rather than token authenticated:
| the only client today is this application's own front end, and giving it
| bearer tokens would mean minting a credential that outlives the session for
| no gain. See bootstrap/app.php for the stateful middleware this group runs.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('api.workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('api.workspaces.store');

    Route::prefix('workspaces/{workspace}')->scopeBindings()->group(function () {
        Route::get('/', [WorkspaceController::class, 'show'])->name('api.workspaces.show');
        Route::patch('/', [WorkspaceController::class, 'update'])->name('api.workspaces.update');

        Route::post('archive', [WorkspaceArchiveController::class, 'store'])->name('api.workspaces.archive');
        Route::post('leave', [WorkspaceLeaveController::class, 'store'])->name('api.workspaces.leave');
        Route::post('ownership/transfer', [WorkspaceOwnershipController::class, 'store'])->name('api.workspaces.ownership.transfer');

        Route::get('members', [WorkspaceMemberController::class, 'index'])->name('api.workspaces.members.index');
        Route::get('members/invitations', [WorkspaceMemberInvitationController::class, 'index'])->name('api.workspaces.members.invitations.index');
        Route::post('members/invitations', [WorkspaceMemberInvitationController::class, 'store'])->name('api.workspaces.members.invitations.store');
        Route::patch('members/{member}', [WorkspaceMemberController::class, 'update'])->name('api.workspaces.members.update');
        Route::delete('members/{member}', [WorkspaceMemberController::class, 'destroy'])->name('api.workspaces.members.destroy');
    });

    Route::post('workspace-invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept'])
        ->name('api.workspace-invitations.accept');

    Route::post('workspace-invitations/{invitation}/revoke', [WorkspaceInvitationController::class, 'revoke'])
        ->name('api.workspace-invitations.revoke');
});

/*
 * Readable without a session: the landing page has to be able to say which
 * workspace a link is for before asking anyone to sign in. The response is
 * limited to that.
 */
Route::get('workspace-invitations/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('api.workspace-invitations.show');
