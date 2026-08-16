<?php

namespace Modules\Playtesting\Domain\Exceptions;

/**
 * Raised when a participant is linked to an account from outside the studio.
 *
 * A guard against disclosure rather than a rule about who may play. Linking a
 * participant to a Barkeep account makes that account's name and address
 * readable through the session, so the account has to be one the caller could
 * already see — somebody who shares the workspace.
 *
 * Nobody is being kept out of the playtest by this. A friend, a stranger at a
 * convention, a member of the local game group: all of them are recorded by
 * display name, which is how the overwhelming majority of participants are
 * recorded anyway. What is refused is the *link*, not the person.
 */
final class ParticipantAccountIsNotOnTheTeam extends PlaytestRuleViolation
{
    private function __construct(public readonly string $userId, string $message)
    {
        parent::__construct($message);
    }

    public static function forUser(string $userId): self
    {
        return new self($userId, __('Only members of this workspace can be linked to a participant. Add them by name instead.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'user_id';
    }
}
