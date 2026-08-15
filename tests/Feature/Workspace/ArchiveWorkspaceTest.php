<?php

use Illuminate\Support\Facades\Event;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\Events\WorkspaceArchived;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->owner)->withSlug('studio')->create();
});

it('lets the owner archive a workspace', function () {
    Event::fake([WorkspaceArchived::class]);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/archive')
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    $workspace = $this->workspace->fresh();

    expect($workspace?->status)->toBe(WorkspaceStatus::Archived)
        ->and($workspace?->archived_at)->not->toBeNull();

    Event::assertDispatched(WorkspaceArchived::class);
});

/**
 * Archival is the end of a workspace's active life, so it stays with the
 * owner rather than with anyone who happens to administer it.
 */
it('does not let an administrator archive a workspace', function () {
    $admin = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($this->workspace)->forUser($admin)->admin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/workspaces/studio/archive')
        ->assertForbidden();

    expect($this->workspace->fresh()?->status)->toBe(WorkspaceStatus::Active);
});

it('keeps the workspace and everything hanging off it', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/archive')
        ->assertOk();

    $this->assertDatabaseHas('workspaces', ['id' => $this->workspace->id]);
    $this->assertDatabaseHas('workspace_members', [
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->owner->id,
    ]);
});

it('keeps an archived workspace readable', function () {
    $archived = Workspace::factory()->ownedBy($this->owner)->withSlug('retired')->archived()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/workspaces/retired')
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    $this->actingAs($this->owner)
        ->getJson('/api/v1/workspaces/retired/members')
        ->assertOk();

    expect($archived->fresh()?->isModifiable())->toBeFalse();
});

it('refuses changes to an archived workspace', function () {
    Workspace::factory()->ownedBy($this->owner)->withSlug('retired')->archived()->create();

    $this->actingAs($this->owner)
        ->patchJson('/api/v1/workspaces/retired', ['name' => 'Back', 'slug' => 'back'])
        ->assertForbidden();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/retired/archive')
        ->assertForbidden();
});

/**
 * A suspended workspace is closed rather than merely frozen, so it is hidden
 * from its own members too.
 */
it('hides a suspended workspace from everyone', function () {
    Workspace::factory()->ownedBy($this->owner)->withSlug('halted')->suspended()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/workspaces/halted')
        ->assertNotFound();
});

it('leaves a suspended workspace out of the switcher list', function () {
    Workspace::factory()->ownedBy($this->owner)->withSlug('halted')->suspended()->create();
    Workspace::factory()->ownedBy($this->owner)->withSlug('retired')->archived()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['slug' => 'halted']);
});

it('archives from the web form and returns to the workspace list', function () {
    $this->actingAs($this->owner)
        ->post(route('workspaces.archive', 'studio'))
        ->assertRedirect(route('workspaces.index'));
});
