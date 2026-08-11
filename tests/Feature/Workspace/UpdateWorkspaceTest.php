<?php

use Illuminate\Support\Facades\Event;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Events\WorkspaceUpdated;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->owner)->withSlug('studio')->create();
});

it('lets the owner rename a workspace', function () {
    Event::fake([WorkspaceUpdated::class]);

    $this->actingAs($this->owner)
        ->patchJson('/api/v1/workspaces/studio', [
            'name' => 'Renamed Studio',
            'slug' => 'renamed-studio',
            'description' => 'Now with more dice.',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed Studio')
        ->assertJsonPath('data.slug', 'renamed-studio');

    Event::assertDispatched(
        WorkspaceUpdated::class,
        fn (WorkspaceUpdated $event) => in_array('slug', $event->changed, strict: true),
    );
});

it('lets an administrator update the workspace', function () {
    $admin = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($this->workspace)->forUser($admin)->admin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/workspaces/studio', [
            'name' => 'Renamed Studio',
            'slug' => 'studio',
        ])
        ->assertOk();
});

it('does not let a plain member update the workspace', function () {
    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($this->workspace)->forUser($member)->create();

    $this->actingAs($member)
        ->patchJson('/api/v1/workspaces/studio', ['name' => 'Renamed', 'slug' => 'renamed'])
        ->assertForbidden();

    expect($this->workspace->fresh()?->name)->not->toBe('Renamed');
});

it('lets a workspace keep its own address', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/workspaces/studio', [
            'name' => 'Same Address, New Name',
            'slug' => 'studio',
        ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'studio');
});

it('will not take an address another workspace is using', function () {
    Workspace::factory()->withSlug('taken')->create();

    $this->actingAs($this->owner)
        ->patchJson('/api/v1/workspaces/studio', ['name' => 'Studio', 'slug' => 'taken'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');

    expect($this->workspace->fresh()?->slug)->toBe('studio');
});

it('requires the address on an update', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/workspaces/studio', ['name' => 'Studio'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('clears a description that is emptied', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/workspaces/studio', [
            'name' => 'Studio',
            'slug' => 'studio',
            'description' => '',
        ])
        ->assertOk()
        ->assertJsonPath('data.description', null);
});

it('sends the web form to wherever the workspace now lives', function () {
    $this->actingAs($this->owner)
        ->patch(route('workspaces.update', 'studio'), [
            'name' => 'Renamed Studio',
            'slug' => 'renamed-studio',
        ])
        ->assertRedirect(route('workspaces.settings.edit', 'renamed-studio'));
});
