<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * A user belongs to many workspaces, and the client keeps track of which one
 * is selected. These tests pin down the half of that the server owns: what it
 * shares, and the fact that the selection carries no authority.
 */
it('shares the account\'s workspaces with every page', function () {
    $user = User::factory()->create();

    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();
    Workspace::factory()->ownedBy($user)->withSlug('beta')->create();
    Workspace::factory()->withSlug('somewhere-else')->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('workspaces.available', 2)
            ->where('workspaces.current', null));
});

it('reports the workspace the current URL is about', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();

    $this->actingAs($user)
        ->get(route('workspaces.show', 'alpha'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('workspaces.current', 'alpha')
            ->where('workspace.data.slug', 'alpha'));
});

/**
 * The switcher list is shared with every page, and each workspace in it is
 * rendered with nine permission answers that all need the caller's role. If
 * those roles were resolved lazily, every page load would cost a query per
 * workspace.
 */
it('resolves the caller\'s role in every listed workspace without a query each', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $index) {
        Workspace::factory()->ownedBy($user)->withSlug("studio-{$index}")->create();
    }

    DB::enableQueryLog();

    $this->actingAs($user)->getJson('/api/v1/workspaces')->assertOk()->assertJsonCount(5, 'data');

    $membershipQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], 'from "workspace_members"'))
        ->count();

    DB::disableQueryLog();

    expect($membershipQueries)->toBeLessThan(5);
});

it('shares nothing with a guest', function () {
    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('workspaces', null));
});

it('lists a workspace somebody was invited into alongside their own', function () {
    $user = User::factory()->create();
    $ownWorkspace = Workspace::factory()->ownedBy($user)->withSlug('mine')->create();
    $joined = Workspace::factory()->withSlug('theirs')->create();

    WorkspaceMember::factory()->inWorkspace($joined)->forUser($user)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($ownWorkspace->isOwnedBy($user))->toBeTrue()
        ->and($joined->isOwnedBy($user))->toBeFalse();
});

/**
 * The permission map is what the client draws its buttons from, so it has to
 * be the policy's answer rather than a second guess at the rules.
 */
it('tells each member what they may actually do', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($owner)->withSlug('studio')->create();

    $admin = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($workspace)->forUser($admin)->admin()->create();

    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($workspace)->forUser($member)->create();

    $this->actingAs($owner)
        ->getJson('/api/v1/workspaces/studio')
        ->assertJsonPath('data.permissions.canArchive', true)
        ->assertJsonPath('data.permissions.canTransferOwnership', true)
        ->assertJsonPath('data.permissions.canChangeRoles', true)
        ->assertJsonPath('data.permissions.canLeave', false);

    $this->actingAs($admin)
        ->getJson('/api/v1/workspaces/studio')
        ->assertJsonPath('data.permissions.canUpdate', true)
        ->assertJsonPath('data.permissions.canInviteMembers', true)
        ->assertJsonPath('data.permissions.canArchive', false)
        ->assertJsonPath('data.permissions.canChangeRoles', false)
        ->assertJsonPath('data.permissions.canLeave', true);

    $this->actingAs($member)
        ->getJson('/api/v1/workspaces/studio')
        ->assertJsonPath('data.permissions.canView', true)
        ->assertJsonPath('data.permissions.canUpdate', false)
        ->assertJsonPath('data.permissions.canInviteMembers', false)
        ->assertJsonPath('data.permissions.canRemoveMembers', false)
        ->assertJsonPath('data.permissions.canLeave', true);
});

it('reports an archived workspace as unchangeable to its owner', function () {
    $owner = User::factory()->create();
    Workspace::factory()->ownedBy($owner)->withSlug('retired')->archived()->create();

    $this->actingAs($owner)
        ->getJson('/api/v1/workspaces/retired')
        ->assertOk()
        ->assertJsonPath('data.permissions.canView', true)
        ->assertJsonPath('data.permissions.canUpdate', false)
        ->assertJsonPath('data.permissions.canArchive', false);
});

it('renders the workspace screens for a member', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $this->actingAs($user)->get(route('workspaces.index'))->assertOk();
    $this->actingAs($user)->get(route('workspaces.create'))->assertOk();
    $this->actingAs($user)->get(route('workspaces.show', 'studio'))->assertOk();
    $this->actingAs($user)->get(route('workspaces.members.index', 'studio'))->assertOk();
    $this->actingAs($user)->get(route('workspaces.settings.edit', 'studio'))->assertOk();
});

it('sends a guest to sign in before any workspace screen', function () {
    $this->get(route('workspaces.index'))->assertRedirect(route('login'));
});
