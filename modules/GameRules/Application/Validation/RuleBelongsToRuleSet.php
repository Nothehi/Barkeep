<?php

namespace Modules\GameRules\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * Requires that a rule id names one of this rule set's own rules.
 *
 * A rule object rather than an `exists:game_rules,id,rule_set_id,…` clause, so that
 * "which rules belong to this rule set" has one definition — the one the
 * catalogue publishes and the commands use — instead of a second copy written in
 * a validator that will drift the first time the lookup changes.
 *
 * The commands prove this again on the way in and raise if it fails. That is not
 * redundant: this exists so the problem lands next to the picker where somebody
 * can fix it, and the command exists so a caller arriving another way cannot
 * write an edge into somebody else's rule system.
 */
final class RuleBelongsToRuleSet implements ValidationRule
{
    public function __construct(private readonly RuleSet $ruleSet) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('Choose a rule.'));

            return;
        }

        if (! app(RuleCatalogue::class)->ruleSetHasRule($this->ruleSet, $value)) {
            $fail(__('That rule belongs to a different rule set.'));
        }
    }
}
