<?php

namespace Modules\Workspace\Infrastructure\Session;

use Illuminate\Contracts\Session\Session;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The workspace the signed in account chose to work in.
 *
 * Held in the session rather than on the account, because Identity sits below
 * Workspace and may not learn about tenancy — a column on `users` would point
 * the dependency the wrong way. The session is also the right lifetime for it:
 * the choice is made at sign in and forgotten at sign out.
 *
 * Only the address is stored, and it carries no authority. Every request is
 * still authorized against the workspace it actually resolves, and a stored
 * address whose membership has since ended simply stops being accepted.
 */
final class ActiveWorkspace
{
    /**
     * The session key the chosen address is kept under.
     */
    public const SESSION_KEY = 'workspace.active';

    public function __construct(private readonly Session $session) {}

    /**
     * The address of the chosen workspace, if a choice has been made.
     */
    public function slug(): ?string
    {
        $slug = $this->session->get(self::SESSION_KEY);

        return is_string($slug) ? $slug : null;
    }

    /**
     * Record the workspace as the one being worked in.
     */
    public function remember(Workspace $workspace): void
    {
        $this->session->put(self::SESSION_KEY, $workspace->slug);
    }

    /**
     * Drop the choice, which sends the account back to the chooser.
     */
    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Drop the choice only if it is the given workspace.
     *
     * Called when somebody stops being a member of the workspace they were
     * working in, so the next screen asks them where to go rather than
     * silently keeping an address they can no longer open.
     */
    public function forgetIf(Workspace $workspace): void
    {
        if ($this->slug() === $workspace->slug) {
            $this->forget();
        }
    }
}
