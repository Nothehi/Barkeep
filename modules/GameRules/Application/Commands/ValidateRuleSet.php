<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Domain\Events\RuleSetValidated;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Analysis\RuleSetValidator;
use Modules\Identity\Domain\Models\User;

/**
 * Check a rule set, on purpose.
 *
 * A command rather than a query only because it dispatches an event. Nothing is
 * written: a validation run is a reading of the rule set as it stands, and
 * storing the result would immediately create a second question — "is this
 * finding still true?" — that the module would then have to keep answering.
 * Recomputing is cheap and always right.
 *
 * The event exists because pressing "Validate" is a fact about how a studio
 * works, and it is worth distinguishing from the dashboard computing the same
 * findings to draw a page. A refresh is not a decision, so the query stays
 * silent.
 */
final class ValidateRuleSet
{
    public function __construct(private readonly RuleSetValidator $validator) {}

    /**
     * @return list<ValidationError>
     */
    public function handle(User $actor, RuleSet $ruleSet): array
    {
        $findings = $this->validator->validate($ruleSet);

        $errors = count(array_filter($findings, fn (ValidationError $finding): bool => $finding->isError()));

        event(new RuleSetValidated(
            ruleSetId: $ruleSet->getKey(),
            errorCount: $errors,
            warningCount: count($findings) - $errors,
            validatedBy: $actor->getKey(),
        ));

        return $findings;
    }
}
