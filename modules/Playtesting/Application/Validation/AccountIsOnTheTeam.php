<?php

namespace Modules\Playtesting\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Infrastructure\Workspace\WorkspaceRoster;

/**
 * Restrict a participant's account link to people who share the workspace.
 *
 * Not a rule about who may play — anyone may play, and most participants have
 * no Barkeep account at all. It is a disclosure guard: linking a participant
 * to an account makes that account's name and address readable through the
 * session, so the account has to be one the caller could already see on the
 * members screen.
 *
 * Somebody outside the workspace is recorded by display name instead, which is
 * how the overwhelming majority of participants are recorded anyway.
 */
class AccountIsOnTheTeam implements ValidationRule
{
    public function __construct(private readonly Game $game) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (! app(WorkspaceRoster::class)->isTeammate($this->game, $value)) {
            $fail(__('Only members of this workspace can be linked to a participant. Add them by name instead.'));
        }
    }
}
