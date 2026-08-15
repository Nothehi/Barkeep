<?php

namespace Modules\Workspace\Infrastructure\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use SensitiveParameter;

/**
 * Delivers a workspace invitation to the address it was addressed to.
 *
 * Sent on demand rather than through a listener: the plaintext token exists
 * only inside the invite use case, and an event carrying it would spread the
 * secret across every listener and queue payload downstream.
 *
 * The notification is not queued, so a mail failure surfaces to the person
 * who clicked "invite" instead of leaving them believing it was sent.
 */
class WorkspaceInvitationNotification extends Notification
{
    public function __construct(
        private readonly WorkspaceInvitation $invitation,
        #[SensitiveParameter] private readonly InvitationToken $token,
        private readonly string $invitedBy,
    ) {}

    /**
     * The delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the invitation email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace->name;

        return (new MailMessage)
            ->subject(__('You have been invited to join :workspace', ['workspace' => $workspace]))
            ->greeting(__('Hello!'))
            ->line(__(':name has invited you to join :workspace on Barkeep as :role.', [
                'name' => $this->invitedBy,
                'workspace' => $workspace,
                'role' => $this->invitation->role->label(),
            ]))
            ->action(__('Accept invitation'), route('workspace-invitations.show', [
                'token' => $this->token->plainText,
            ]))
            ->line(__('This invitation expires on :date.', [
                'date' => $this->invitation->expires_at->toFormattedDateString(),
            ]))
            ->line(__('If you were not expecting this invitation, you can ignore this email.'));
    }
}
