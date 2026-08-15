<?php

use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;

it('only lets an active workspace change', function () {
    expect(WorkspaceStatus::Active->allowsModification())->toBeTrue()
        ->and(WorkspaceStatus::Archived->allowsModification())->toBeFalse()
        ->and(WorkspaceStatus::Suspended->allowsModification())->toBeFalse();
});

/**
 * Archiving retires a workspace without hiding it: its games, playtests and
 * history have to stay readable. Suspension is the one state that closes it.
 */
it('keeps an archived workspace readable but closes a suspended one', function () {
    expect(WorkspaceStatus::Active->isReadable())->toBeTrue()
        ->and(WorkspaceStatus::Archived->isReadable())->toBeTrue()
        ->and(WorkspaceStatus::Suspended->isReadable())->toBeFalse();
});

it('explains itself when it denies an action', function (WorkspaceStatus $status) {
    expect($status->deniedReason())->not->toBe('')
        ->and($status->label())->not->toBe('');
})->with(WorkspaceStatus::cases());

it('only lets a pending invitation be accepted', function () {
    expect(InvitationStatus::Pending->isAcceptable())->toBeTrue()
        ->and(InvitationStatus::Accepted->isAcceptable())->toBeFalse()
        ->and(InvitationStatus::Revoked->isAcceptable())->toBeFalse()
        ->and(InvitationStatus::Expired->isAcceptable())->toBeFalse();
});
