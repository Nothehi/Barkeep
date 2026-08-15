<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceInvitationAccepted;
use Modules\Workspace\Domain\Events\WorkspaceInvitationCreated;
use Modules\Workspace\Domain\Events\WorkspaceInvitationRevoked;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Infrastructure\Notifications\WorkspaceInvitationNotification;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->owner)->withSlug('studio')->create();
});

it('invites an address and emails it the link', function () {
    Notification::fake();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
            'role' => 'admin',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'designer@example.com')
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonPath('data.status', 'pending');

    Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
});

it('normalises the invited address', function () {
    Notification::fake();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => '  Designer@Example.COM ',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'designer@example.com');
});

/**
 * The token is the whole security of an invitation. It exists in plaintext
 * only inside the email; everything else — API responses, the events, the
 * database — sees a digest at most.
 */
it('never returns the token in any form', function () {
    Notification::fake();

    $response = $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
        ])
        ->assertCreated();

    $invitation = WorkspaceInvitation::query()->sole();

    expect($response->json('data'))->not->toHaveKeys(['token', 'token_hash'])
        ->and($response->getContent())->not->toContain($invitation->token_hash);
});

it('stores only a digest of the token', function () {
    $token = InvitationToken::generate();

    $invitation = WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->withToken($token)
        ->create();

    expect($invitation->token_hash)->not->toBe($token->plainText)
        ->and($invitation->token_hash)->toBe(hash('sha256', $token->plainText));
});

it('does not let a plain member invite anybody', function () {
    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($this->workspace)->forUser($member)->create();

    $this->actingAs($member)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
        ])
        ->assertForbidden();
});

it('will not invite somebody who is already a member', function () {
    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($this->workspace)->forUser($member)->create();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => $member->email,
        ])
        ->assertStatus(409);
});

/**
 * Two administrators clicking "invite" on the same address must not produce
 * two live links; the partial unique index settles it.
 */
it('will not issue a second pending invitation for the same address', function () {
    Notification::fake();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to('designer@example.com')
        ->create();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
        ])
        ->assertStatus(409);

    expect(WorkspaceInvitation::query()->count())->toBe(1);
});

it('lets an address be invited again after the first was revoked', function () {
    Notification::fake();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to('designer@example.com')
        ->revoked()
        ->create();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
        ])
        ->assertCreated();
});

it('will not invite somebody straight to ownership', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
            'role' => 'owner',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
});

it('announces the invitation without carrying the token', function () {
    Notification::fake();
    Event::fake([WorkspaceInvitationCreated::class]);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'designer@example.com',
        ])
        ->assertCreated();

    Event::assertDispatched(
        WorkspaceInvitationCreated::class,
        fn (WorkspaceInvitationCreated $event) => $event->email === 'designer@example.com'
            && ! property_exists($event, 'token'),
    );
});

it('accepts a valid invitation and creates the membership', function () {
    Event::fake([WorkspaceInvitationAccepted::class]);

    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    $invitation = WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->asAdmin()
        ->withToken($token)
        ->create();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertCreated()
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonPath('data.user_id', $invited->id);

    expect($this->workspace->fresh()?->hasMember($invited))->toBeTrue()
        ->and($invitation->fresh()?->status)->toBe(InvitationStatus::Accepted);

    Event::assertDispatched(WorkspaceInvitationAccepted::class);
});

/**
 * The role comes out of the invitation, never off the request, so a holder
 * cannot upgrade themselves on the way in.
 */
it('takes the role from the invitation and not from the request', function () {
    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->withToken($token)
        ->create();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept", [
            'role' => 'admin',
            'workspace_id' => Workspace::factory()->create()->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.role', 'member')
        ->assertJsonPath('data.workspace_id', $this->workspace->id);
});

it('will not accept an invitation twice', function () {
    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->withToken($token)
        ->create();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertCreated();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertStatus(409);

    expect($this->workspace->members()->where('user_id', $invited->id)->count())->toBe(1);
});

it('will not accept an expired invitation', function () {
    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->withToken($token)
        ->expired()
        ->create();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertStatus(409);

    expect($this->workspace->fresh()?->hasMember($invited))->toBeFalse();
});

it('will not accept a revoked invitation', function () {
    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->withToken($token)
        ->revoked()
        ->create();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertStatus(409);
});

/**
 * An invitation is addressed to one person. Somebody else holding the link —
 * a forwarded email, a shared inbox — must not be able to redeem it.
 */
it('will not let the wrong account redeem an invitation', function () {
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to('designer@example.com')
        ->withToken($token)
        ->create();

    $someoneElse = User::factory()->create();

    $this->actingAs($someoneElse)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertStatus(409);

    expect($this->workspace->fresh()?->hasMember($someoneElse))->toBeFalse();
});

it('treats a guessed token as no invitation at all', function () {
    $invited = User::factory()->create();

    $this->actingAs($invited)
        ->postJson('/api/v1/workspace-invitations/not-a-real-token/accept')
        ->assertNotFound();
});

it('requires an account to accept', function () {
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->withToken($token)
        ->create();

    $this->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertUnauthorized();
});

/**
 * The landing page has to work before anyone signs in, so this endpoint is
 * public — which is why it says only which workspace the link is for.
 */
it('shows a guest which workspace a link is for, and nothing more', function () {
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to('designer@example.com')
        ->withToken($token)
        ->create();

    $this->getJson("/api/v1/workspace-invitations/{$token->plainText}")
        ->assertOk()
        ->assertJsonPath('data.workspace.name', $this->workspace->name)
        ->assertJsonPath('data.email', 'designer@example.com')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonMissingPath('data.id')
        ->assertJsonMissingPath('data.workspace_id')
        ->assertJsonMissingPath('data.invited_by');
});

it('reports an expired link as expired without being asked to sweep it', function () {
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->withToken($token)
        ->expired()
        ->create();

    $this->getJson("/api/v1/workspace-invitations/{$token->plainText}")
        ->assertOk()
        ->assertJsonPath('data.status', 'expired');
});

it('lets an administrator revoke a pending invitation', function () {
    Event::fake([WorkspaceInvitationRevoked::class]);

    $invitation = WorkspaceInvitation::factory()->inWorkspace($this->workspace)->create();

    $this->actingAs($this->owner)
        ->postJson("/api/v1/workspace-invitations/{$invitation->id}/revoke")
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked');

    expect($invitation->fresh()?->status)->toBe(InvitationStatus::Revoked);

    Event::assertDispatched(WorkspaceInvitationRevoked::class);
});

it('does not let a plain member revoke an invitation', function () {
    $member = User::factory()->create();
    WorkspaceMember::factory()->inWorkspace($this->workspace)->forUser($member)->create();

    $invitation = WorkspaceInvitation::factory()->inWorkspace($this->workspace)->create();

    $this->actingAs($member)
        ->postJson("/api/v1/workspace-invitations/{$invitation->id}/revoke")
        ->assertForbidden();
});

it('will not revoke an invitation that has been accepted', function () {
    $invitation = WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->accepted()
        ->create();

    $this->actingAs($this->owner)
        ->postJson("/api/v1/workspace-invitations/{$invitation->id}/revoke")
        ->assertStatus(409);
});

it('leaves expired invitations out of the pending list', function () {
    WorkspaceInvitation::factory()->inWorkspace($this->workspace)->create();
    WorkspaceInvitation::factory()->inWorkspace($this->workspace)->expired()->create();
    WorkspaceInvitation::factory()->inWorkspace($this->workspace)->revoked()->create();
    WorkspaceInvitation::factory()->inWorkspace($this->workspace)->accepted()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/workspaces/studio/members/invitations')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending');
});

it('will not invite into an archived workspace', function () {
    $archived = Workspace::factory()->ownedBy($this->owner)->withSlug('retired')->archived()->create();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/retired/members/invitations', [
            'email' => 'designer@example.com',
        ])
        ->assertForbidden();

    expect($archived->invitations()->count())->toBe(0);
});

it('renders the invitation landing page for a guest', function () {
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->withToken($token)
        ->create();

    $this->get(route('workspace-invitations.show', $token->plainText))
        ->assertOk();
});

it('sends an accepted invitation into the workspace', function () {
    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->withToken($token)
        ->create();

    $this->actingAs($invited)
        ->post(route('workspace-invitations.accept', $token->plainText))
        ->assertRedirect(route('workspaces.show', 'studio'));
});

it('invites an unregistered address and waits for them to register', function () {
    Notification::fake();

    $this->actingAs($this->owner)
        ->postJson('/api/v1/workspaces/studio/members/invitations', [
            'email' => 'nobody-yet@example.com',
        ])
        ->assertCreated();

    /** Workspace never creates accounts; that stays with Identity. */
    expect(User::query()->where('email', 'nobody-yet@example.com')->exists())->toBeFalse()
        ->and($this->workspace->invitations()->where('email', 'nobody-yet@example.com')->exists())
        ->toBeTrue();
});

it('gives an invited administrator their role once they accept', function () {
    $invited = User::factory()->create();
    $token = InvitationToken::generate();

    WorkspaceInvitation::factory()
        ->inWorkspace($this->workspace)
        ->to($invited->email)
        ->asAdmin()
        ->withToken($token)
        ->create();

    $this->actingAs($invited)
        ->postJson("/api/v1/workspace-invitations/{$token->plainText}/accept")
        ->assertCreated();

    expect($this->workspace->fresh()?->memberFor($invited)?->role)
        ->toBe(WorkspaceRole::Admin);
});
