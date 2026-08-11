<?php

use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * Workspace is a security boundary, so this file exists to try to cross it.
 *
 * Two accounts, two workspaces, no overlap. Every endpoint is asked whether
 * it will tell the wrong person something about the other side.
 *
 * A non-member gets 404 rather than 403 throughout. That is deliberate: 403
 * would confirm the workspace exists, which turns the slug — a value that
 * appears in shareable links — into an enumeration oracle.
 */
beforeEach(function () {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();

    $this->alpha = Workspace::factory()->ownedBy($this->alice)->withSlug('alpha')->create();
    $this->beta = Workspace::factory()->ownedBy($this->bob)->withSlug('beta')->create();
});

it('does not admit that another account\'s workspace exists', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/beta')
        ->assertNotFound();

    $this->actingAs($this->bob)
        ->getJson('/api/v1/workspaces/alpha')
        ->assertNotFound();
});

it('lists only the workspaces an account belongs to', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'alpha');

    $this->actingAs($this->bob)
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'beta');
});

it('lists every workspace an account does belong to', function () {
    $second = Workspace::factory()->ownedBy($this->alice)->withSlug('alpha-two')->create();

    WorkspaceMember::factory()->inWorkspace($this->beta)->forUser($this->alice)->create();

    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    expect($second->fresh()?->slug)->toBe('alpha-two');
});

it('will not show another workspace\'s members', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/beta/members')
        ->assertNotFound();
});

it('will not show another workspace\'s invitations', function () {
    $this->actingAs($this->alice)
        ->getJson('/api/v1/workspaces/beta/members/invitations')
        ->assertNotFound();
});

it('will not let an outsider change another workspace\'s settings', function () {
    $this->actingAs($this->alice)
        ->patchJson('/api/v1/workspaces/beta', ['name' => 'Taken', 'slug' => 'taken'])
        ->assertNotFound();

    expect($this->beta->fresh()?->name)->not->toBe('Taken');
});

it('will not let an outsider invite into another workspace', function () {
    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/members/invitations', [
            'email' => 'someone@example.com',
        ])
        ->assertNotFound();

    expect(WorkspaceInvitation::query()->count())->toBe(0);
});

it('will not let an outsider archive another workspace', function () {
    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/archive')
        ->assertNotFound();

    expect($this->beta->fresh()?->status->value)->toBe('active');
});

it('will not let an outsider take another workspace', function () {
    $aliceInAlpha = $this->alpha->memberFor($this->alice);

    $this->actingAs($this->alice)
        ->postJson('/api/v1/workspaces/beta/ownership/transfer', [
            'member_id' => $aliceInAlpha?->id,
        ])
        ->assertNotFound();

    expect($this->beta->fresh()?->owner_id)->toBe($this->bob->id);
});

/**
 * The scoped route binding is the load-bearing part here: a membership id
 * from another workspace must not resolve at all, even for somebody who is
 * an administrator of the workspace named in the URL.
 */
it('will not act on a membership belonging to another workspace', function () {
    $bobInBeta = $this->beta->memberFor($this->bob);

    $this->actingAs($this->alice)
        ->patchJson("/api/v1/workspaces/alpha/members/{$bobInBeta?->id}", ['role' => 'admin'])
        ->assertNotFound();

    $this->actingAs($this->alice)
        ->deleteJson("/api/v1/workspaces/alpha/members/{$bobInBeta?->id}")
        ->assertNotFound();

    expect($bobInBeta?->fresh()?->role->value)->toBe('owner');
});

it('will not revoke an invitation belonging to another workspace', function () {
    $invitation = WorkspaceInvitation::factory()->inWorkspace($this->beta)->create();

    $this->actingAs($this->alice)
        ->postJson("/api/v1/workspace-invitations/{$invitation->id}/revoke")
        ->assertNotFound();

    expect($invitation->fresh()?->status->value)->toBe('pending');
});

it('hides another workspace\'s screens as well as its endpoints', function () {
    $this->actingAs($this->alice)->get(route('workspaces.show', 'beta'))->assertNotFound();
    $this->actingAs($this->alice)->get(route('workspaces.members.index', 'beta'))->assertNotFound();
    $this->actingAs($this->alice)->get(route('workspaces.settings.edit', 'beta'))->assertNotFound();
});

it('keeps a plain member out of the actions reserved to administrators', function () {
    $member = User::factory()->create();

    WorkspaceMember::factory()->inWorkspace($this->alpha)->forUser($member)->create();

    /** A member can see the workspace and who is in it. */
    $this->actingAs($member)->getJson('/api/v1/workspaces/alpha')->assertOk();
    $this->actingAs($member)->getJson('/api/v1/workspaces/alpha/members')->assertOk();

    /**
     * They are refused, not hidden from: they already know the workspace
     * exists, so 403 is the honest answer.
     */
    $this->actingAs($member)
        ->patchJson('/api/v1/workspaces/alpha', ['name' => 'Renamed', 'slug' => 'renamed'])
        ->assertForbidden();

    $this->actingAs($member)
        ->postJson('/api/v1/workspaces/alpha/members/invitations', ['email' => 'x@example.com'])
        ->assertForbidden();

    $this->actingAs($member)
        ->getJson('/api/v1/workspaces/alpha/members/invitations')
        ->assertForbidden();
});
