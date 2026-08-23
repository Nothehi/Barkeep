<?php

namespace Modules\GameRules\Infrastructure\Analysis;

use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSetSummary;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * How much of a rule system there is, and how much of it holds together.
 *
 * The row of numbers across the top of the rules dashboard. Counted rather than
 * loaded: a heading that read the whole rulebook to render "24" would be the most
 * expensive part of the page.
 *
 * Static analysis in the sense section 31 of the brief means. Nothing here runs a
 * turn, evaluates a condition or simulates anything — it counts records and asks
 * the validator what it found. A studio's confidence in the rules screen rests on
 * that being obvious, which is why the analyser is a separate class that cannot
 * write and takes findings as an argument rather than deciding anything itself.
 *
 * The findings come in from outside for a second reason: the dashboard already has
 * them, and computing them twice to draw one page would double the work of the
 * most expensive thing on it.
 */
final class RuleSetAnalyser
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * Count everything in a rule set, and fold in what the validator found.
     *
     * @param  list<ValidationError>  $findings
     */
    public function summarise(RuleSet $ruleSet, array $findings): RuleSetSummary
    {
        $errors = 0;
        $warnings = 0;

        foreach ($findings as $finding) {
            if ($finding->isError()) {
                $errors++;

                continue;
            }

            $warnings++;
        }

        return new RuleSetSummary(
            rules: $ruleSet->rules()->count(),
            mechanics: $ruleSet->mechanics()->count(),
            phases: $ruleSet->phases()->count(),
            transitions: $ruleSet->transitions()->count(),
            actions: $ruleSet->actions()->count(),
            requirements: $ruleSet->requirements()->count(),
            conditions: $ruleSet->conditions()->count(),
            conditionGroups: $ruleSet->conditionGroups()->count(),
            effects: $ruleSet->effects()->count(),
            triggers: $ruleSet->triggers()->count(),
            victoryConditions: $ruleSet->victoryConditions()->count(),
            defeatConditions: $ruleSet->defeatConditions()->count(),
            endConditions: $ruleSet->endConditions()->count(),
            references: $this->structure->referencesOf($ruleSet)->count(),
            warnings: $warnings,
            errors: $errors,
        );
    }
}
