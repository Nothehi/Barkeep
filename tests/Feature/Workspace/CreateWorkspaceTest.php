<?php

use Illuminate\Support\Facades\Event;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceCreated;
use Modules\Workspace\Domain\Models\Workspace;

it('creates a workspace and its owner membership together', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/workspaces', [
        'name' => 'My Board Game Studio',
        'slug' => 'my-board-game-studio',
        'description' => 'Where the prototypes live.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Board Game Studio')
        ->assertJsonPath('data.slug', 'my-board-game-studio')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.owner_id', $user->id);

    $workspace = Workspace::query()->where('slug', 'my-board-game-studio')->sole();

    $this->assertDatabaseHas('workspace_members', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner->value,
    ]);
});

it('derives an address when none is supplied', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', ['name' => 'Prototype Lab'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'prototype-lab');
});

/**
 * A derived address may be adjusted around a collision, because the person
 * did not ask for that specific address in the first place.
 */
it('numbers a derived address around a collision', function () {
    $user = User::factory()->create();
    Workspace::factory()->withSlug('prototype-lab')->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', ['name' => 'Prototype Lab'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'prototype-lab-2');
});

/**
 * An address somebody typed is theirs, so a collision is reported rather than
 * quietly worked around.
 */
it('reports a collision on an address that was supplied', function () {
    $user = User::factory()->create();
    Workspace::factory()->withSlug('prototype-lab')->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', [
            'name' => 'Prototype Lab',
            'slug' => 'prototype-lab',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('rejects an address that is not URL safe', function (string $slug) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', ['name' => 'Prototype Lab', 'slug' => $slug])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
})->with([
    'spaces' => 'prototype lab',
    'punctuation' => 'prototype_lab',
    'accents' => 'prototypé-lab',
    'reserved' => 'settings',
    'too short' => 'ab',
]);

/**
 * Case is the one thing normalised rather than refused: folding it cannot
 * change which workspace the address means.
 */
it('folds the case of an address rather than rejecting it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', [
            'name' => 'Prototype Lab',
            'slug' => 'Prototype-Lab',
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'prototype-lab');
});

it('requires a name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('announces the new workspace', function () {
    Event::fake([WorkspaceCreated::class]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/workspaces', ['name' => 'Prototype Lab'])
        ->assertCreated();

    Event::assertDispatched(
        WorkspaceCreated::class,
        fn (WorkspaceCreated $event) => $event->ownerId === $user->id
            && $event->slug === 'prototype-lab',
    );
});

it('will not create a workspace for a guest', function () {
    $this->postJson('/api/v1/workspaces', ['name' => 'Prototype Lab'])
        ->assertUnauthorized();

    expect(Workspace::query()->count())->toBe(0);
});

it('creates a workspace from the web form and lands inside it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), [
            'name' => 'Prototype Lab',
            'slug' => 'prototype-lab',
        ])
        ->assertRedirect(route('workspaces.show', 'prototype-lab'));
});
