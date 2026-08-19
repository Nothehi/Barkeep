<?php

namespace Modules\GameEconomy\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Models\BalanceProfile;

/**
 * Requires that a resource id names one of this configuration's own resources.
 *
 * A rule object rather than an `exists:resource_types,id,balance_profile_id,…`
 * clause, so that "which resources belong to this profile" has one definition —
 * the one the catalogue publishes and the commands use — instead of a second
 * copy written in a validator that will drift the first time the lookup changes.
 *
 * The commands prove this again on the way in and raise if it fails. That is not
 * redundant: this exists so the problem lands next to the resource picker where
 * somebody can fix it, and the command exists so a caller arriving another way
 * cannot write a cost priced in another studio's wood.
 */
final class ResourceBelongsToProfile implements ValidationRule
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
            $fail(__('Choose a resource.'));

            return;
        }

        if (! app(EconomyCatalogue::class)->profileHasResource($this->profile, $value)) {
            $fail(__('That resource belongs to a different balance profile.'));
        }
    }
}
