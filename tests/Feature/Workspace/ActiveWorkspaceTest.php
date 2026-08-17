<?php

use Inertia\Testing\AssertableInertia;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * An account can belong to several workspaces, and nearly everything inside
 * the app is about exactly one of them. So signing in is not enough on its
 * own: the account has to say which workspace it is working in before the
 * app's own home opens, and the answer has to keep holding up afterwards.
 */
it('asks which workspace to work in before opening the dashboard', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.select'));
});

it('offers only the workspaces the account belongs to', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();
    Workspace::factory()->ownedBy($user)->withSlug('beta')->create();
    Workspace::factory()->withSlug('somewhere-else')->create();

    $this->actingAs($user)
        ->get(route('workspaces.select'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('workspaces/select')
            ->has('workspaces.data', 2));
});

/**
 * There is only one thing somebody with nowhere to go can do from the
 * chooser, so they are sent to do it rather than shown an empty list.
 */
it('sends an account that belongs nowhere straight to creating a workspace', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.select'));

    $this->actingAs($user)
        ->get(route('workspaces.select'))
        ->assertRedirect(route('workspaces.create'));
});

it('opens the dashboard once a workspace has been chosen', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();

    $this->actingAs($user)
        ->post(route('workspaces.activate', 'alpha'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

/**
 * Choosing a workspace is exactly as restricted as opening one: the policy
 * hides a workspace the account does not belong to rather than refusing it,
 * so addresses cannot be enumerated through the chooser either.
 */
it('will not choose a workspace the account does not belong to', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('mine')->create();
    Workspace::factory()->withSlug('theirs')->create();

    $this->actingAs($user)
        ->post(route('workspaces.activate', 'theirs'))
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.select'));
});

it('names the chosen workspace on a page whose URL names none', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();

    $this->actingAs($user)->post(route('workspaces.activate', 'alpha'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('workspaces.current', 'alpha'));
});

/**
 * The URL is still the authority on what a screen is about. Opening another
 * workspace's page reports that one, whatever was chosen at sign in.
 */
it('lets the URL name the workspace over the one that was chosen', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('alpha')->create();
    Workspace::factory()->ownedBy($user)->withSlug('beta')->create();

    $this->actingAs($user)->post(route('workspaces.activate', 'alpha'));

    $this->actingAs($user)
        ->get(route('workspaces.show', 'beta'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('workspaces.current', 'beta'));
});

/**
 * The choice is re-checked against membership on every visit, so being
 * removed from the workspace somebody was working in asks them again instead
 * of leaving them pointed somewhere they can no longer open.
 */
it('asks again once the chosen workspace stops being theirs', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($owner)->withSlug('studio')->create();

    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($workspace)->forUser($member)->create();

    $this->actingAs($member)->post(route('workspaces.activate', 'studio'));
    $this->actingAs($member)->get(route('dashboard'))->assertOk();

    $workspace->members()->where('user_id', $member->id)->delete();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.select'));
});

it('treats creating a workspace as choosing it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), ['name' => 'Prototype Lab'])
        ->assertRedirect(route('workspaces.show', 'prototype-lab'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('workspaces.current', 'prototype-lab'));
});

it('forgets the choice when the account leaves that workspace', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($owner)->withSlug('studio')->create();

    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($workspace)->forUser($member)->create();

    $this->actingAs($member)->post(route('workspaces.activate', 'studio'));

    $this->actingAs($member)
        ->post(route('workspaces.leave', 'studio'))
        ->assertRedirect(route('workspaces.index'));

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertRedirect(route('workspaces.select'));
});

it('sends a guest to sign in before the chooser', function () {
    $this->get(route('workspaces.select'))->assertRedirect(route('login'));
});
