<?php

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceMemberRemoved;
use Modules\Workspace\Domain\Events\WorkspaceMemberRoleChanged;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->owner)->withSlug('studio')->create();

    $this->admin = User::factory()->create();
    $this->adminMembership = WorkspaceMember::factory()
        ->inWorkspace($this->workspace)
        ->forUser($this->admin)
        ->admin()
        ->create();

    $this->member = User::factory()->create();
    $this->memberMembership = WorkspaceMember::factory()
        ->inWorkspace($this->workspace)
        ->forUser($this->member)
        ->create();
});

it('shows the roster to everyone in the workspace', function (string $actor) {
    $this->actingAs($this->{$actor})
        ->getJson('/api/v1/workspaces/studio/members')
        ->assertOk()
        ->assertJsonCount(3, 'data');
})->with(['owner', 'admin', 'member']);

it('lists the owner first', function () {
    $this->actingAs($this->member)
        ->getJson('/api/v1/workspaces/studio/members')
        ->assertOk()
        ->assertJsonPath('data.0.role', 'owner')
        ->assertJsonPath('data.0.user_id', $this->owner->id)
        ->assertJsonPath('data.1.role', 'admin');
});

it('never exposes an invitation token through the members surface', function () {
    $this->actingAs($this->owner)
        ->getJson('/api/v1/workspaces/studio/members')
        ->assertOk()
        ->assertJsonMissing(['token_hash'])
        ->assertJsonMissing(['token']);
});

it('lets the owner promote a member', function () {
    Event::fake([WorkspaceMemberRoleChanged::class]);

    $this->actingAs($this->owner)
        ->patchJson("/api/v1/workspaces/studio/members/{$this->memberMembership->id}", [
            'role' => 'admin',
        ])
        ->assertOk()
        ->assertJsonPath('data.role', 'admin');

    expect($this->memberMembership->fresh()?->role)->toBe(WorkspaceRole::Admin);

    Event::assertDispatched(
        WorkspaceMemberRoleChanged::class,
        fn (WorkspaceMemberRoleChanged $event) => $event->from === WorkspaceRole::Member
            && $event->to === WorkspaceRole::Admin,
    );
});

it('lets the owner demote an administrator', function () {
    $this->actingAs($this->owner)
        ->patchJson("/api/v1/workspaces/studio/members/{$this->adminMembership->id}", [
            'role' => 'member',
        ])
        ->assertOk();

    expect($this->adminMembership->fresh()?->role)->toBe(WorkspaceRole::Member);
});

/**
 * Letting administrators promote each other would make the role meaningless
 * — any one of them could hand out their own level to anybody.
 */
it('does not let an administrator change roles', function () {
    $this->actingAs($this->admin)
        ->patchJson("/api/v1/workspaces/studio/members/{$this->memberMembership->id}", [
            'role' => 'admin',
        ])
        ->assertForbidden();

    expect($this->memberMembership->fresh()?->role)->toBe(WorkspaceRole::Member);
});

it('will not demote the owner through an ordinary role change', function () {
    $ownerMembership = $this->workspace->memberFor($this->owner);

    $this->actingAs($this->owner)
        ->patchJson("/api/v1/workspaces/studio/members/{$ownerMembership?->id}", [
            'role' => 'member',
        ])
        ->assertForbidden();

    expect($ownerMembership?->fresh()?->role)->toBe(WorkspaceRole::Owner);
});

/**
 * Ownership arriving over the wire is refused at the boundary: accepting it
 * would give the workspace two owners.
 */
it('will not accept ownership as a role', function () {
    $this->actingAs($this->owner)
        ->patchJson("/api/v1/workspaces/studio/members/{$this->memberMembership->id}", [
            'role' => 'owner',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
});

it('lets an administrator remove a plain member', function () {
    Event::fake([WorkspaceMemberRemoved::class]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/workspaces/studio/members/{$this->memberMembership->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('workspace_members', ['id' => $this->memberMembership->id]);

    Event::assertDispatched(
        WorkspaceMemberRemoved::class,
        fn (WorkspaceMemberRemoved $event) => $event->userId === $this->member->id
            && ! $event->wasVoluntary(),
    );
});

it('does not let one administrator eject another', function () {
    $other = User::factory()->create();
    $otherMembership = WorkspaceMember::factory()
        ->inWorkspace($this->workspace)
        ->forUser($other)
        ->admin()
        ->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/workspaces/studio/members/{$otherMembership->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('workspace_members', ['id' => $otherMembership->id]);
});

it('never lets the owner be removed', function (string $actor) {
    $ownerMembership = $this->workspace->memberFor($this->owner);

    $this->actingAs($this->{$actor})
        ->deleteJson("/api/v1/workspaces/studio/members/{$ownerMembership?->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('workspace_members', [
        'id' => $ownerMembership?->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
})->with(['owner', 'admin']);

it('does not let a plain member remove anybody', function () {
    $this->actingAs($this->member)
        ->deleteJson("/api/v1/workspaces/studio/members/{$this->adminMembership->id}")
        ->assertForbidden();
});

it('points somebody removing themselves at leaving instead', function () {
    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/workspaces/studio/members/{$this->adminMembership->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('workspace_members', ['id' => $this->adminMembership->id]);
});

it('lets a member leave', function () {
    Event::fake([WorkspaceMemberRemoved::class]);

    $this->actingAs($this->member)
        ->postJson('/api/v1/workspaces/studio/leave')
        ->assertNoContent();

    $this->assertDatabaseMissing('workspace_members', ['id' => $this->memberMembership->id]);

    Event::assertDispatched(
        WorkspaceMemberRemoved::class,
        fn (WorkspaceMemberRemoved $event) => $event->wasVoluntary(),
    );
});

/**
 * A workspace with no owner has nobody who can archive it or manage its
 * members, so the owner has to hand it over before walking away.
 */
it('does not let the owner leave', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/leave')
        ->assertForbidden();

    $this->assertDatabaseHas('workspace_members', [
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->owner->id,
    ]);
});

it('does not let somebody leave a workspace they are not in', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->postJson('/api/v1/workspaces/studio/leave')
        ->assertNotFound();
});

it('refuses a second membership for the same account', function () {
    expect(fn () => WorkspaceMember::factory()
        ->inWorkspace($this->workspace)
        ->forUser($this->member)
        ->create())
        ->toThrow(UniqueConstraintViolationException::class);
});
