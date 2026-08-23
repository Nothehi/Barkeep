<?php

namespace Modules\GameRules\Application\Services;

use Modules\GameRules\Domain\Exceptions\RecordDoesNotBelongToRuleSet;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The one place a record named in a request body is proved to belong here.
 *
 * Most identifiers in this module arrive as route segments and are resolved
 * through their parent by the router. Eight do not, because there is no natural
 * URL for them:
 *
 * - the parent a rule or a phase is nested under,
 * - the phase a rule or an action happens in,
 * - both ends of a phase transition,
 * - the condition and the trigger that guard one,
 * - the rule or the action a requirement or an effect belongs to,
 * - the condition an outcome is measured by, or that joins a group,
 * - the rule another rule references.
 *
 * Every one of those is the invariant the database cannot express. A foreign key
 * proves the phase exists; only a lookup scoped by rule set proves it belongs to
 * *this* rule system. So all of them go through here, and the proof is structural
 * rather than a comparison: the record is resolved through the set, so one from a
 * different rule system is never found rather than being found and rejected.
 *
 * Every method has a `find` twin that returns null, used by validation — which
 * wants to report the problem next to the field rather than to raise it.
 */
final class RuleCatalogue
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * Resolve one of a set's rules, or fail.
     *
     * @throws RecordDoesNotBelongToRuleSet
     */
    public function ruleOf(RuleSet $ruleSet, string $ruleId, string $field = 'rule_id'): GameRule
    {
        return $this->findRuleOf($ruleSet, $ruleId)
            ?? throw RecordDoesNotBelongToRuleSet::forRule($ruleSet->getKey(), $ruleId, $field);
    }

    public function findRuleOf(RuleSet $ruleSet, string $ruleId): ?GameRule
    {
        return $this->structure->findRuleInRuleSet($ruleSet, $ruleId);
    }

    public function ruleSetHasRule(RuleSet $ruleSet, string $ruleId): bool
    {
        return $this->findRuleOf($ruleSet, $ruleId) !== null;
    }

    /**
     * Resolve one of a set's phases, or fail.
     *
     * @throws RecordDoesNotBelongToRuleSet
     */
    public function phaseOf(RuleSet $ruleSet, string $phaseId, string $field = 'phase_id'): GamePhase
    {
        return $this->findPhaseOf($ruleSet, $phaseId)
            ?? throw RecordDoesNotBelongToRuleSet::forPhase($ruleSet->getKey(), $phaseId, $field);
    }

    public function findPhaseOf(RuleSet $ruleSet, string $phaseId): ?GamePhase
    {
        return $this->structure->findPhaseInRuleSet($ruleSet, $phaseId);
    }

    public function ruleSetHasPhase(RuleSet $ruleSet, string $phaseId): bool
    {
        return $this->findPhaseOf($ruleSet, $phaseId) !== null;
    }

    /**
     * Resolve one of a set's actions, or fail.
     *
     * @throws RecordDoesNotBelongToRuleSet
     */
    public function actionOf(RuleSet $ruleSet, string $actionId, string $field = 'action_id'): RuleAction
    {
        return $this->findActionOf($ruleSet, $actionId)
            ?? throw RecordDoesNotBelongToRuleSet::forAction($ruleSet->getKey(), $actionId, $field);
    }

    public function findActionOf(RuleSet $ruleSet, string $actionId): ?RuleAction
    {
        return $this->structure->findActionInRuleSet($ruleSet, $actionId);
    }

    public function ruleSetHasAction(RuleSet $ruleSet, string $actionId): bool
    {
        return $this->findActionOf($ruleSet, $actionId) !== null;
    }

    /**
     * Resolve one of a set's conditions, or fail.
     *
     * @throws RecordDoesNotBelongToRuleSet
     */
    public function conditionOf(RuleSet $ruleSet, string $conditionId, string $field = 'condition_id'): RuleCondition
    {
        return $this->findConditionOf($ruleSet, $conditionId)
            ?? throw RecordDoesNotBelongToRuleSet::forCondition($ruleSet->getKey(), $conditionId, $field);
    }

    public function findConditionOf(RuleSet $ruleSet, string $conditionId): ?RuleCondition
    {
        return $this->structure->findConditionInRuleSet($ruleSet, $conditionId);
    }

    public function ruleSetHasCondition(RuleSet $ruleSet, string $conditionId): bool
    {
        return $this->findConditionOf($ruleSet, $conditionId) !== null;
    }

    /**
     * Resolve one of a set's triggers, or fail.
     *
     * @throws RecordDoesNotBelongToRuleSet
     */
    public function triggerOf(RuleSet $ruleSet, string $triggerId, string $field = 'trigger_id'): RuleTrigger
    {
        return $this->findTriggerOf($ruleSet, $triggerId)
            ?? throw RecordDoesNotBelongToRuleSet::forTrigger($ruleSet->getKey(), $triggerId, $field);
    }

    public function findTriggerOf(RuleSet $ruleSet, string $triggerId): ?RuleTrigger
    {
        return $this->structure->findTriggerInRuleSet($ruleSet, $triggerId);
    }

    public function ruleSetHasTrigger(RuleSet $ruleSet, string $triggerId): bool
    {
        return $this->findTriggerOf($ruleSet, $triggerId) !== null;
    }

    /**
     * Resolve the rule another rule references, through the referrer's own set.
     *
     * The overload that matters most in practice: a reference arrives naming one
     * rule (from the route) and another (from the body), and the two are only a
     * valid pair if they share a rule set. Because `rule_references` has no
     * `rule_set_id` of its own, this lookup is the *only* thing keeping an edge
     * from spanning two rule systems.
     *
     * @throws RecordDoesNotBelongToRuleSet
     */
    public function referencedRuleFor(GameRule $rule, string $referencedRuleId): GameRule
    {
        $ruleSet = $rule->ruleSet;

        if ($ruleSet === null) {
            throw RecordDoesNotBelongToRuleSet::forRule(
                $rule->rule_set_id,
                $referencedRuleId,
                'referenced_rule_id',
            );
        }

        return $this->ruleOf($ruleSet, $referencedRuleId, 'referenced_rule_id');
    }

    /**
     * Resolve the rule another rule references, or return null.
     */
    public function findReferencedRuleFor(GameRule $rule, string $referencedRuleId): ?GameRule
    {
        $ruleSet = $rule->ruleSet;

        return $ruleSet === null ? null : $this->findRuleOf($ruleSet, $referencedRuleId);
    }
}
