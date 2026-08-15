<?php

use Illuminate\Support\Facades\Event;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceOwnershipTransferred;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->owner)->withSlug('studio')->create();

    $this->successor = User::factory()->create();
    $this->successorMembership = WorkspaceMember::factory()
        ->inWorkspace($this->workspace)
        ->forUser($this->successor)
        ->admin()
        ->create();
});

/**
 * The workspace records its owner twice — as `owner_id` and as the membership
 * carrying the owner role — and the whole module trusts the two to agree.
 * Every assertion here checks both halves.
 */
it('moves ownership and both records of it together', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.owner_id', $this->successor->id);

    $workspace = $this->workspace->fresh();

    expect($workspace?->owner_id)->toBe($this->successor->id)
        ->and($workspace?->ownerMembership()?->user_id)->toBe($this->successor->id)
        ->and($this->successorMembership->fresh()?->role)->toBe(WorkspaceRole::Owner);
});

it('leaves the outgoing owner as an administrator by default', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
        ])
        ->assertOk();

    expect($this->workspace->fresh()?->memberFor($this->owner)?->role)
        ->toBe(WorkspaceRole::Admin);
});

it('lets the outgoing owner choose to stay as a plain member', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
            'role' => 'member',
        ])
        ->assertOk();

    expect($this->workspace->fresh()?->memberFor($this->owner)?->role)
        ->toBe(WorkspaceRole::Member);
});

it('leaves exactly one owner behind', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
        ])
        ->assertOk();

    $owners = $this->workspace->members()->where('role', WorkspaceRole::Owner)->count();

    expect($owners)->toBe(1);
});

it('announces the transfer with both sides of the swap', function () {
    Event::fake([WorkspaceOwnershipTransferred::class]);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
        ])
        ->assertOk();

    Event::assertDispatched(
        WorkspaceOwnershipTransferred::class,
        fn (WorkspaceOwnershipTransferred $event) => $event->previousOwnerId === $this->owner->id
            && $event->newOwnerId === $this->successor->id,
    );
});

it('only lets the owner transfer ownership', function () {
    $this->actingAs($this->successor)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
        ])
        ->assertForbidden();

    expect($this->workspace->fresh()?->owner_id)->toBe($this->owner->id);
});

it('will not hand the workspace to somebody who is not in it', function () {
    $outsiderWorkspace = Workspace::factory()->withSlug('elsewhere')->create();
    $outsiderMembership = $outsiderWorkspace->ownerMembership();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $outsiderMembership?->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('member_id');

    expect($this->workspace->fresh()?->owner_id)->toBe($this->owner->id);
});

it('will not transfer to the current owner', function () {
    $ownerMembership = $this->workspace->memberFor($this->owner);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $ownerMembership?->id,
        ])
        ->assertStatus(409);

    expect($this->workspace->fresh()?->owner_id)->toBe($this->owner->id);
});

it('requires a member to transfer to', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('member_id');
});

it('lets the previous owner leave once they have handed it over', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/ownership/transfer', [
            'member_id' => $this->successorMembership->id,
        ])
        ->assertOk();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/leave')
        ->assertNoContent();

    expect($this->workspace->fresh()?->hasMember($this->owner))->toBeFalse();
});
