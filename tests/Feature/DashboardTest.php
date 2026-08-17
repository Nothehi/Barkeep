<?php

use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

/**
 * The dashboard is about the workspace being worked in, so reaching it takes
 * both a session and a chosen workspace. Choosing one is the step signing in
 * lands on — see tests/Feature/Workspace/ActiveWorkspaceTest.php.
 */
test('authenticated users can visit the dashboard once they have chosen a workspace', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $this->actingAs($user)->post(route('workspaces.activate', 'studio'));

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users without a chosen workspace are asked to choose one', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertRedirect(route('workspaces.select'));
});
