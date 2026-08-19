<?php

namespace Modules\GameEconomy\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Models\BalanceProfile;

/**
 * Requires that an action id names one of this configuration's own actions.
 *
 * The same shape as {@see ResourceBelongsToProfile}, for the other half of the
 * pair a variable may point at.
 */
final class ActionBelongsToProfile implements ValidationRule
{
    public function __construct(private readonly BalanceProfile $profile) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('Choose an action.'));

            return;
        }

        if (! app(EconomyCatalogue::class)->profileHasAction($this->profile, $value)) {
            $fail(__('That action belongs to a different balance profile.'));
        }
    }
}
