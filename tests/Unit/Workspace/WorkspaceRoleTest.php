<?php

use Modules\Workspace\Domain\Enums\WorkspaceRole;

it('ranks the roles from owner down', function () {
    expect(WorkspaceRole::Owner->outranks(WorkspaceRole::Admin))->toBeTrue()
        ->and(WorkspaceRole::Admin->outranks(WorkspaceRole::Member))->toBeTrue()
        ->and(WorkspaceRole::Member->outranks(WorkspaceRole::Admin))->toBeFalse();
});

it('treats a role as being at least itself', function () {
    expect(WorkspaceRole::Admin->atLeast(WorkspaceRole::Admin))->toBeTrue()
        ->and(WorkspaceRole::Admin->outranks(WorkspaceRole::Admin))->toBeFalse();
});

it('lets the owner do anything an admin or member can', function () {
    expect(WorkspaceRole::Owner->atLeast(WorkspaceRole::Admin))->toBeTrue()
        ->and(WorkspaceRole::Owner->atLeast(WorkspaceRole::Member))->toBeTrue();
});

/**
 * Ownership never arrives over the wire. It moves through an explicit
 * transfer, so that a workspace always has exactly one owner.
 */
it('does not treat ownership as something an administrator can grant', function () {
    expect(WorkspaceRole::Owner->isAssignable())->toBeFalse()
        ->and(WorkspaceRole::Admin->isAssignable())->toBeTrue()
        ->and(WorkspaceRole::Member->isAssignable())->toBeTrue()
        ->and(WorkspaceRole::assignable())
        ->toBe([WorkspaceRole::Admin, WorkspaceRole::Member]);
});

it('has a label for every role', function (WorkspaceRole $role) {
    expect($role->label())->not->toBe('');
})->with(WorkspaceRole::cases());
