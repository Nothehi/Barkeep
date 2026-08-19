<?php

namespace Modules\GameEconomy\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Models\BalanceScenario;

/**
 * Requires that a variable id names a number in the scenario's own profile.
 *
 * Without this, a scenario could store an override that changes nothing anybody
 * can see: the row would be written, the profile it applies to would not contain
 * the variable, and the only symptom would be a number that refused to move.
 */
final class VariableBelongsToScenario implements ValidationRule
{
    public function __construct(private readonly BalanceScenario $scenario) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('Choose a variable.'));

            return;
        }

        if (app(EconomyCatalogue::class)->findVariableForScenario($this->scenario, $value) === null) {
            $fail(__('That variable belongs to a different balance profile.'));
        }
    }
}
