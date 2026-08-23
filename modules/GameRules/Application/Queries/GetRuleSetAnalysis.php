<?php

namespace Modules\GameRules\Application\Queries;

use Modules\GameRules\Application\DTOs\RuleSetAnalysis;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Analysis\RuleGraphBuilder;
use Modules\GameRules\Infrastructure\Analysis\RuleSetAnalyser;
use Modules\GameRules\Infrastructure\Analysis\RuleSetValidator;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Everything the rules dashboard draws, in one read.
 *
 * The whole screen comes from here rather than from a dozen endpoints, and the
 * reason is that its sections are not independent: the findings are *about* the
 * rules, the phases and the actions, and a page that fetched them separately would
 * spend part of its life showing errors about a rule set it had not finished
 * receiving.
 *
 * Silent. Unlike `AnalyseRuleSet`, this dispatches nothing — a page refresh is not
 * a decision, and a studio's event stream should not fill up with the fact that
 * somebody had a tab open.
 *
 * The findings are computed once and split into errors and warnings here rather
 * than by the client, so the counts on the summary and the two lists below it can
 * never disagree.
 */
final class GetRuleSetAnalysis
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleSetValidator $validator,
        private readonly RuleSetAnalyser $analyser,
        private readonly RuleGraphBuilder $graphs,
    ) {}

    public function handle(RuleSet $ruleSet): RuleSetAnalysis
    {
        $findings = $this->validator->validate($ruleSet);

        $errors = [];
        $warnings = [];

        foreach ($findings as $finding) {
            if ($finding->isError()) {
                $errors[] = $finding;

                continue;
            }

            $warnings[] = $finding;
        }

        return new RuleSetAnalysis(
            summary: $this->analyser->summarise($ruleSet, $findings),
            rules: $this->structure->rulesOf($ruleSet),
            mechanics: $this->structure->mechanicsOf($ruleSet),
            phases: $this->structure->phasesOf($ruleSet),
            transitions: $this->structure->transitionsOf($ruleSet),
            actions: $this->structure->actionsOf($ruleSet),
            requirements: $this->structure->requirementsOf($ruleSet),
            conditions: $this->structure->conditionsOf($ruleSet),
            conditionGroups: $this->structure->conditionGroupsOf($ruleSet),
            effects: $this->structure->effectsOf($ruleSet),
            triggers: $this->structure->triggersOf($ruleSet),
            references: $this->structure->referencesOf($ruleSet),
            victoryConditions: $this->structure->victoryConditionsOf($ruleSet),
            defeatConditions: $this->structure->defeatConditionsOf($ruleSet),
            endConditions: $this->structure->endConditionsOf($ruleSet),
            graph: $this->graphs->build($ruleSet),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * The findings alone, for callers that only need the list.
     *
     * @return list<ValidationError>
     */
    public function findings(RuleSet $ruleSet): array
    {
        return $this->validator->validate($ruleSet);
    }
}
