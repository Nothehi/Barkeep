<?php

namespace Modules\GameRules\Application\DTOs;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Domain\ValueObjects\RuleGraph;
use Modules\GameRules\Domain\ValueObjects\RuleSetSummary;
use Modules\GameRules\Domain\ValueObjects\ValidationError;

/**
 * Everything the rules dashboard draws, in one object.
 *
 * The whole screen reads from a single response, which is a deliberate departure
 * from how some of the other modules' screens work. The reason is that these
 * sections are not independent: the findings are *about* the rules, the phases and
 * the actions, and a page that fetched them separately would spend part of its
 * life showing errors about a rule set it had not finished receiving.
 *
 * It is also why the collections travel with the analysis rather than being
 * fetched beside it. They were already loaded to compute the findings; sending
 * them back a second time from a second endpoint would double the work and open a
 * window in which the two disagreed.
 *
 * Static, in the sense section 31 means. Nothing here was executed, simulated or
 * played.
 *
 * @property-read Collection<int, GameRule> $rules
 */
final readonly class RuleSetAnalysis
{
    /**
     * @param  Collection<int, GameRule>  $rules
     * @param  Collection<int, RuleMechanic>  $mechanics
     * @param  Collection<int, GamePhase>  $phases
     * @param  Collection<int, PhaseTransition>  $transitions
     * @param  Collection<int, RuleAction>  $actions
     * @param  Collection<int, RuleRequirement>  $requirements
     * @param  Collection<int, RuleCondition>  $conditions
     * @param  Collection<int, ConditionGroup>  $conditionGroups
     * @param  Collection<int, RuleEffect>  $effects
     * @param  Collection<int, RuleTrigger>  $triggers
     * @param  Collection<int, RuleReference>  $references
     * @param  Collection<int, VictoryCondition>  $victoryConditions
     * @param  Collection<int, DefeatCondition>  $defeatConditions
     * @param  Collection<int, GameEndCondition>  $endConditions
     * @param  list<ValidationError>  $errors
     * @param  list<ValidationError>  $warnings
     */
    public function __construct(
        public RuleSetSummary $summary,
        public Collection $rules,
        public Collection $mechanics,
        public Collection $phases,
        public Collection $transitions,
        public Collection $actions,
        public Collection $requirements,
        public Collection $conditions,
        public Collection $conditionGroups,
        public Collection $effects,
        public Collection $triggers,
        public Collection $references,
        public Collection $victoryConditions,
        public Collection $defeatConditions,
        public Collection $endConditions,
        public RuleGraph $graph,
        public array $errors,
        public array $warnings,
    ) {}

    /**
     * Every finding, worst first.
     *
     * Split into two lists on the way in because the screen draws them under two
     * headings, and joined here for the callers that want the count or the whole
     * list in one pass.
     *
     * @return list<ValidationError>
     */
    public function findings(): array
    {
        return [...$this->errors, ...$this->warnings];
    }
}
