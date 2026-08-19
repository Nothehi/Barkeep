<?php

namespace Modules\PrototypeIteration\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * The boundary check on the Playtesting side, at the form.
 *
 * An iteration's evidence has to be evidence about the same project. The playtest
 * id arrives in a request body — there is no route segment for it, deliberately,
 * so that this module never binds a Playtesting model — and it is resolved through
 * the iteration's own game using Playtesting's published query.
 *
 * The check goes through this module's Playtesting adapter rather than through an
 * `exists` rule on the `playtests` table. That is not fussiness: an `exists` rule
 * here would be a second, unscoped definition of "a playtest of this game" written
 * in a validator, sitting outside the one file that is supposed to know
 * Playtesting exists at all.
 */
class PlaytestBelongsToGame implements ValidationRule
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
            $fail(__('Choose the playtest that tested this iteration.'));

            return;
        }

        if (! app(PlaytestEvidence::class)->gameHasPlaytest($this->game, $value)) {
            $fail(__('That is not a playtest of this game.'));
        }
    }
}
