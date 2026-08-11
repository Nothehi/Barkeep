<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Presentation\Http\Controllers\Web\WorkspaceController;
use Modules\Workspace\Presentation\Http\Controllers\Web\WorkspaceInvitationController;
use Modules\Workspace\Presentation\Http\Controllers\Web\WorkspaceMemberController;
use Modules\Workspace\Presentation\Http\Controllers\Web\WorkspaceSettingsController;

/*
|--------------------------------------------------------------------------
| Workspace screens
|--------------------------------------------------------------------------
|
| Workspaces are addressed by slug, so the URLs read the way people talk
| about them: /app/workspaces/my-board-game-studio. Route model binding
| resolves the workspace; every action then authorizes against the resolved
| model, never against the identifier in the URL.
|
| `scopeBindings()` is what keeps members and invitations from leaking across
| workspaces: a `{member}` is resolved through the bound workspace's own
| membership, so an id from somewhere else 404s before any handler runs.
|
*/

Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    Route::get('workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::get('workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    Route::prefix('workspaces/{workspace}')->scopeBindings()->group(function () {
        Route::get('/', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::patch('/', [WorkspaceSettingsController::class, 'update'])->name('workspaces.update');
        Route::post('leave', [WorkspaceController::class, 'leave'])->name('workspaces.leave');

        Route::get('settings', [WorkspaceSettingsController::class, 'edit'])->name('workspaces.settings.edit');
        Route::post('archive', [WorkspaceSettingsController::class, 'archive'])->name('workspaces.archive');
        Route::post('ownership', [WorkspaceSettingsController::class, 'transferOwnership'])->name('workspaces.ownership.transfer');

        Route::get('members', [WorkspaceMemberController::class, 'index'])->name('workspaces.members.index');
        Route::post('members/invitations', [WorkspaceMemberController::class, 'invite'])->name('workspaces.members.invite');
        Route::delete('members/invitations/{invitation}', [WorkspaceMemberController::class, 'revokeInvitation'])
            ->name('workspaces.members.invitations.revoke');
        Route::patch('members/{member}', [WorkspaceMemberController::class, 'update'])->name('workspaces.members.update');
        Route::delete('members/{member}', [WorkspaceMemberController::class, 'destroy'])->name('workspaces.members.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Invitation links
|--------------------------------------------------------------------------
|
| Reachable without a session, because the person holding the link may not
| have registered yet. Viewing an invitation says only which workspace it is
| for; redeeming it requires an account, and the account has to be the one the
| invitation was addressed to.
|
*/

Route::get('workspace-invitations/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('workspace-invitations.show');

Route::post('workspace-invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept'])
    ->middleware(['auth', 'verified'])
    ->name('workspace-invitations.accept');
