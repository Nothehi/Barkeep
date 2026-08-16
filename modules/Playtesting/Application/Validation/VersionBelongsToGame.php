<?php

namespace Modules\Playtesting\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Infrastructure\GameDesign\GameCatalogue;

/**
 * The module's central invariant, checked at the form boundary.
 *
 * A playtest names a game and a version, and the two arrive from different
 * places: the game from a resolved route binding, the version as an id in the
 * request body. This proves they agree while the caller is still looking at a
 * form, so the failure appears next to the field rather than as a 409 after
 * the fact.
 *
 * It is not the only place the pairing is checked, and it is not the important
 * one — the command proves it again through the same adapter, because a form
 * request only guards the HTTP door. This exists so somebody who picked the
 * wrong version gets told which field is wrong.
 *
 * Delegating to the catalogue rather than writing an `exists` rule with a
 * `where` clause keeps Playtesting out of GameDesign's tables: the lookup goes
 * through the game's own relation, so there is one definition of "this game's
 * versions" rather than two.
 */
class VersionBelongsToGame implements ValidationRule
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
            $fail(__('Choose a version to test.'));

            return;
        }

        if (! app(GameCatalogue::class)->gameHasVersion($this->game, $value)) {
            /*
             * Worded as "not a version of this game" rather than "not found",
             * because both possible causes — a version that does not exist and
             * one belonging to somebody else's game — should read the same to
             * the caller. Distinguishing them would confirm the existence of
             * records outside their workspace.
             */
            $fail(__('That is not a version of this game.'));
        }
    }
}
