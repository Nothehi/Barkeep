<?php

namespace Modules\PrototypeIteration\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Services\PrototypeCatalogue;

/**
 * The other half of the central invariant, checked at the form boundary.
 *
 * The more important half, and the reason is worth stating. A game version from
 * another project is caught by GameDesign's own scoping the moment anything looks
 * at it; a *prototype* version is this module's own record, so nothing outside the
 * module would ever notice the mismatch. An iteration pointing at another game's
 * build would read perfectly, appear in the right list, and describe a cycle
 * nobody ran against a design nobody was working on.
 *
 * The lookup goes through the catalogue, which resolves the version *through* the
 * game's own prototypes — so a version from elsewhere is not compared and rejected,
 * it simply is not found. The command proves the same thing again; this exists so
 * a designer who picked the wrong build is told which field is wrong.
 */
class PrototypeVersionBelongsToGame implements ValidationRule
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
            $fail(__('Choose the prototype version this iteration is about.'));

            return;
        }

        if (! app(PrototypeCatalogue::class)->gameHasVersion($this->game, $value)) {
            $fail(__('That is not a prototype version of this game.'));
        }
    }
}
