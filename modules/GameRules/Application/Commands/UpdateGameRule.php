<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\GameRuleData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\GameRuleUpdated;
use Modules\GameRules\Domain\Exceptions\CircularRuleHierarchy;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Analysis\CycleDetector;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Reword a rule, retype it, move it in the tree, or retire it.
 *
 * What the rule *requires* and *does* is not editable from here — requirements
 * and effects have their own commands, because each is a separate row and editing
 * one must not be able to disturb another.
 *
 * The interesting case is reparenting. `parent_rule_id` arriving in the body is
 * checked twice: once for belonging to this rule set, and once for whether the
 * move would make the rule its own ancestor. The second check is the reason
 * {@see CycleDetector} exists, and it happens *before* the write, so the refusal
 * names the move the caller just made rather than appearing later as a finding
 * about data that is already wrong.
 *
 * Sending `parent_rule_id` as null promotes the rule to the top level, which can
 * never cycle and is answered immediately.
 */
final class UpdateGameRule
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly CycleDetector $cycles,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, GameRule $rule, GameRuleData $data): GameRule
    {
        $ruleSet = $rule->ruleSet;

        if ($ruleSet === null) {
            return $rule;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $rule->name) {
            $slug = RuleSlug::fromName($data->name);

            if ($this->structure->ruleSetHasRuleSlug($ruleSet, $slug, $rule->getKey())) {
                throw RuleSlugIsTaken::forRule($slug);
            }

            $rule->name = $data->name;
            $rule->slug = $slug->value;
        }

        if ($data->sent('description')) {
            $rule->description = $data->description;
        }

        if ($data->sent('parent_rule_id')) {
            $rule->parent_rule_id = $this->resolveParent($rule, $data->parentRuleId);
        }

        if ($data->sent('phase_id')) {
            $rule->phase_id = $data->phaseId === null
                ? null
                : $this->catalogue->phaseOf($ruleSet, $data->phaseId)->getKey();
        }

        if ($data->ruleType !== null) {
            $rule->rule_type = $data->ruleType;
        }

        if ($data->status !== null) {
            $rule->status = $data->status;
        }

        if ($data->position !== null) {
            $rule->position = $data->position;
        }

        $changed = array_keys($rule->getDirty());

        $rule->save();

        if ($changed !== []) {
            event(new GameRuleUpdated(
                ruleId: $rule->getKey(),
                ruleSetId: $rule->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $rule;
    }

    /**
     * Work out the new parent, refusing a move that would close a loop.
     *
     * @throws CircularRuleHierarchy
     */
    private function resolveParent(GameRule $rule, ?string $parentRuleId): ?string
    {
        if ($parentRuleId === null) {
            return null;
        }

        $ruleSet = $rule->ruleSet;

        if ($ruleSet === null) {
            return null;
        }

        $parent = $this->catalogue->ruleOf($ruleSet, $parentRuleId, 'parent_rule_id');

        $parents = $this->structure->ruleParentMap($ruleSet);

        if ($this->cycles->wouldCreateCycle($parents, $rule->getKey(), $parent->getKey())) {
            throw CircularRuleHierarchy::forRule($rule->getKey(), $parent->getKey());
        }

        return $parent->getKey();
    }
}
