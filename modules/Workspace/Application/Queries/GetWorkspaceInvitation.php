<?php

namespace Modules\Workspace\Application\Queries;

use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Infrastructure\Persistence\Repositories\WorkspaceRepository;
use SensitiveParameter;

/**
 * Resolve the invitation behind a token.
 *
 * Used to render the "you have been invited to X" screen before the person
 * has signed in, so what the caller does with the result must stay limited to
 * the workspace's name — see the public invitation resource.
 *
 * @see PublicWorkspaceInvitationResource
 */
final class GetWorkspaceInvitation
{
    public function __construct(private readonly WorkspaceRepository $workspaces) {}

    public function handle(#[SensitiveParameter] InvitationToken $token): ?WorkspaceInvitation
    {
        return $this->workspaces->findInvitationByToken($token);
    }
}
