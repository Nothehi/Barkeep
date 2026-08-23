<?php

namespace Modules\GameRules\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
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
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\Identity\Domain\Models\User;

/**
 * Copy a rule system into a fresh draft.
 *
 * The operation the whole of section 55 depends on. An active rule set cannot be
 * edited, because the rules are what a session was played under and changing them
 * rewrites what every playtest against them means. So the way forward is to
 * clone, change the copy, and activate it — which is also how a designer would
 * describe what they are doing.
 *
 * That only works if cloning is *cheap and complete*. A clone that dropped the
 * transitions, or that shared a condition row with its ancestor, would make the
 * second step quietly destructive and push people straight back to wanting to
 * edit the original.
 *
 * ## Complete, and independent
 *
 * Fourteen kinds of record are copied — rules, mechanics, phases, transitions,
 * actions, requirements, conditions, groups, group memberships, effects,
 * triggers, and the three kinds of outcome — plus the references between rules.
 * Every one gets a new id, and every pointer between them is rewritten to the new
 * ids. Nothing in the copy shares a row with the original, which is what makes
 * changing it safe and is exactly what the isolation tests assert.
 *
 * ## How the id rewriting works
 *
 * One map per table, built as the rows are written and read when the rows that
 * point at them are written. The order below is the dependency order, and the two
 * self-referencing tables — rules and phases, which have parents in their own
 * table — are written in two passes: every row first with a null parent, then the
 * parents set from the map. That avoids needing the map to be complete before it
 * is built, and avoids sorting the tree.
 *
 * ## What is not copied
 *
 * The status, which always starts at draft: a clone is a working copy, and one
 * that arrived active would displace the rule set it was copied *from* the moment
 * it was made.
 *
 * The whole thing runs in one transaction. A rule set half-cloned is worse than
 * one not cloned at all, because the missing half is invisible.
 */
final class RuleSetCloner
{
    /**
     * How many records the last clone copied.
     *
     * Reported in `RuleSetCloned` so a consumer gets "copied 84 records" without
     * loading both sets to count them.
     */
    private int $copied = 0;

    /**
     * Copy a rule set, and everything in it, into a new draft.
     */
    public function clone(RuleSet $source, User $actor, string $name, ?string $description = null): RuleSet
    {
        return DB::transaction(function () use ($source, $actor, $name, $description): RuleSet {
            $this->copied = 0;

            $target = new RuleSet;
            $target->fill([
                'name' => $name,
                'description' => $description ?? $source->description,
            ]);
            $target->game_version_id = $source->game_version_id;
            $target->status = RuleSetStatus::Draft;
            $target->cloned_from_rule_set_id = $source->getKey();
            $target->created_by = $actor->getKey();
            $target->save();

            $phases = $this->copyPhases($source, $target);
            $conditions = $this->copyConditions($source, $target);
            $triggers = $this->copyTriggers($source, $target);

            $this->copyMechanics($source, $target);

            $rules = $this->copyRules($source, $target, $phases);
            $actions = $this->copyActions($source, $target, $phases);

            $this->copyTransitions($source, $target, $phases, $conditions, $triggers);
            $this->copyRequirements($source, $target, $rules, $actions);
            $this->copyEffects($source, $target, $rules, $actions);
            $this->copyConditionGroups($source, $target, $conditions);
            $this->copyOutcomes($source, $target, $conditions);
            $this->copyReferences($source, $rules);

            return $target->setRelation('version', $source->version);
        });
    }

    /**
     * How many records the last clone wrote, not counting the rule set itself.
     */
    public function recordsCopied(): int
    {
        return $this->copied;
    }

    /**
     * Copy the phases, then rewrite their parents.
     *
     * @return array<string, string> old id => new id
     */
    private function copyPhases(RuleSet $source, RuleSet $target): array
    {
        $map = [];
        $parents = [];

        foreach ($source->phases()->orderBy('position')->get() as $phase) {
            $copy = new GamePhase;
            $copy->rule_set_id = $target->getKey();
            $copy->name = $phase->name;
            $copy->slug = $phase->slug;
            $copy->description = $phase->description;
            $copy->phase_type = $phase->phase_type;
            $copy->status = $phase->status;
            $copy->position = $phase->position;
            $copy->save();

            $map[$phase->getKey()] = $copy->getKey();

            if ($phase->parent_phase_id !== null) {
                $parents[$phase->getKey()] = $phase->parent_phase_id;
            }

            $this->copied++;
        }

        foreach ($parents as $oldId => $oldParentId) {
            $this->setParent(GamePhase::query()->whereKey($map[$oldId])->first(), 'parent_phase_id', $map[$oldParentId] ?? null);
        }

        return $map;
    }

    /**
     * @return array<string, string> old id => new id
     */
    private function copyConditions(RuleSet $source, RuleSet $target): array
    {
        $map = [];

        foreach ($source->conditions()->get() as $condition) {
            $copy = new RuleCondition;
            $copy->rule_set_id = $target->getKey();
            $copy->name = $condition->name;
            $copy->description = $condition->description;
            $copy->condition_type = $condition->condition_type;
            $copy->operator = $condition->operator;
            $copy->value = $condition->value;
            $copy->save();

            $map[$condition->getKey()] = $copy->getKey();
            $this->copied++;
        }

        return $map;
    }

    /**
     * @return array<string, string> old id => new id
     */
    private function copyTriggers(RuleSet $source, RuleSet $target): array
    {
        $map = [];

        foreach ($source->triggers()->orderBy('position')->get() as $trigger) {
            $copy = new RuleTrigger;
            $copy->rule_set_id = $target->getKey();
            $copy->name = $trigger->name;
            $copy->description = $trigger->description;
            $copy->trigger_type = $trigger->trigger_type;
            $copy->position = $trigger->position;
            $copy->save();

            $map[$trigger->getKey()] = $copy->getKey();
            $this->copied++;
        }

        return $map;
    }

    private function copyMechanics(RuleSet $source, RuleSet $target): void
    {
        foreach ($source->mechanics()->orderBy('position')->get() as $mechanic) {
            $copy = new RuleMechanic;
            $copy->rule_set_id = $target->getKey();
            $copy->name = $mechanic->name;
            $copy->slug = $mechanic->slug;
            $copy->description = $mechanic->description;
            $copy->category = $mechanic->category;
            $copy->position = $mechanic->position;
            $copy->save();

            $this->copied++;
        }
    }

    /**
     * Copy the rules, then rewrite their parents.
     *
     * The creator is carried over rather than reattributed to whoever cloned.
     * "Who wrote this rule" is a fact about the rule; the clone's own
     * `created_by` records who made the copy.
     *
     * @param  array<string, string>  $phases
     * @return array<string, string> old id => new id
     */
    private function copyRules(RuleSet $source, RuleSet $target, array $phases): array
    {
        $map = [];
        $parents = [];

        foreach ($source->rules()->orderBy('position')->get() as $rule) {
            $copy = new GameRule;
            $copy->rule_set_id = $target->getKey();
            $copy->phase_id = $rule->phase_id === null ? null : ($phases[$rule->phase_id] ?? null);
            $copy->name = $rule->name;
            $copy->slug = $rule->slug;
            $copy->description = $rule->description;
            $copy->rule_type = $rule->rule_type;
            $copy->status = $rule->status;
            $copy->position = $rule->position;
            $copy->created_by = $rule->created_by;
            $copy->save();

            $map[$rule->getKey()] = $copy->getKey();

            if ($rule->parent_rule_id !== null) {
                $parents[$rule->getKey()] = $rule->parent_rule_id;
            }

            $this->copied++;
        }

        foreach ($parents as $oldId => $oldParentId) {
            $this->setParent(GameRule::query()->whereKey($map[$oldId])->first(), 'parent_rule_id', $map[$oldParentId] ?? null);
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $phases
     * @return array<string, string> old id => new id
     */
    private function copyActions(RuleSet $source, RuleSet $target, array $phases): array
    {
        $map = [];

        foreach ($source->actions()->orderBy('position')->get() as $action) {
            $copy = new RuleAction;
            $copy->rule_set_id = $target->getKey();
            $copy->phase_id = $action->phase_id === null ? null : ($phases[$action->phase_id] ?? null);
            $copy->name = $action->name;
            $copy->slug = $action->slug;
            $copy->description = $action->description;
            $copy->action_type = $action->action_type;
            $copy->status = $action->status;
            $copy->economy_action_slug = $action->economy_action_slug;
            $copy->position = $action->position;
            $copy->save();

            $map[$action->getKey()] = $copy->getKey();
            $this->copied++;
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $phases
     * @param  array<string, string>  $conditions
     * @param  array<string, string>  $triggers
     */
    private function copyTransitions(RuleSet $source, RuleSet $target, array $phases, array $conditions, array $triggers): void
    {
        foreach ($source->transitions()->orderBy('position')->get() as $transition) {
            $from = $phases[$transition->from_phase_id] ?? null;
            $to = $phases[$transition->to_phase_id] ?? null;

            /*
             * An edge whose ends did not come across is dropped rather than
             * written with a dangling id. This is unreachable for a coherent
             * source and is the safe reading of an incoherent one: the validator
             * reports the missing exit, where a broken foreign key would surface
             * as a 500 on the next page load.
             */
            if ($from === null || $to === null) {
                continue;
            }

            $copy = new PhaseTransition;
            $copy->rule_set_id = $target->getKey();
            $copy->from_phase_id = $from;
            $copy->to_phase_id = $to;
            $copy->condition_id = $transition->condition_id === null ? null : ($conditions[$transition->condition_id] ?? null);
            $copy->trigger_id = $transition->trigger_id === null ? null : ($triggers[$transition->trigger_id] ?? null);
            $copy->position = $transition->position;
            $copy->save();

            $this->copied++;
        }
    }

    /**
     * @param  array<string, string>  $rules
     * @param  array<string, string>  $actions
     */
    private function copyRequirements(RuleSet $source, RuleSet $target, array $rules, array $actions): void
    {
        foreach ($source->requirements()->orderBy('position')->get() as $requirement) {
            $copy = new RuleRequirement;
            $copy->rule_set_id = $target->getKey();
            $copy->rule_id = $requirement->rule_id === null ? null : ($rules[$requirement->rule_id] ?? null);
            $copy->action_id = $requirement->action_id === null ? null : ($actions[$requirement->action_id] ?? null);
            $copy->requirement_type = $requirement->requirement_type;
            $copy->description = $requirement->description;
            $copy->value = $requirement->value;
            $copy->economy_resource_slug = $requirement->economy_resource_slug;
            $copy->position = $requirement->position;
            $copy->save();

            $this->copied++;
        }
    }

    /**
     * @param  array<string, string>  $rules
     * @param  array<string, string>  $actions
     */
    private function copyEffects(RuleSet $source, RuleSet $target, array $rules, array $actions): void
    {
        foreach ($source->effects()->orderBy('position')->get() as $effect) {
            $copy = new RuleEffect;
            $copy->rule_set_id = $target->getKey();
            $copy->rule_id = $effect->rule_id === null ? null : ($rules[$effect->rule_id] ?? null);
            $copy->action_id = $effect->action_id === null ? null : ($actions[$effect->action_id] ?? null);
            $copy->effect_type = $effect->effect_type;
            $copy->target = $effect->target;
            $copy->value = $effect->value;
            $copy->description = $effect->description;
            $copy->economy_resource_slug = $effect->economy_resource_slug;
            $copy->position = $effect->position;
            $copy->save();

            $this->copied++;
        }
    }

    /**
     * @param  array<string, string>  $conditions
     */
    private function copyConditionGroups(RuleSet $source, RuleSet $target, array $conditions): void
    {
        foreach ($source->conditionGroups()->with('memberships')->get() as $group) {
            $copy = new ConditionGroup;
            $copy->rule_set_id = $target->getKey();
            $copy->name = $group->name;
            $copy->description = $group->description;
            $copy->logic_operator = $group->logic_operator;
            $copy->save();

            $this->copied++;

            foreach ($group->memberships as $membership) {
                $conditionId = $conditions[$membership->condition_id] ?? null;

                if ($conditionId === null) {
                    continue;
                }

                $member = new ConditionGroupCondition;
                $member->condition_group_id = $copy->getKey();
                $member->condition_id = $conditionId;
                $member->position = $membership->position;
                $member->save();

                $this->copied++;
            }
        }
    }

    /**
     * @param  array<string, string>  $conditions
     */
    private function copyOutcomes(RuleSet $source, RuleSet $target, array $conditions): void
    {
        $tables = [
            [$source->victoryConditions(), VictoryCondition::class],
            [$source->defeatConditions(), DefeatCondition::class],
            [$source->endConditions(), GameEndCondition::class],
        ];

        foreach ($tables as [$relation, $class]) {
            foreach ($relation->orderBy('priority')->get() as $outcome) {
                /** @var VictoryCondition|DefeatCondition|GameEndCondition $copy */
                $copy = new $class;
                $copy->rule_set_id = $target->getKey();
                $copy->name = $outcome->name;
                $copy->description = $outcome->description;
                $copy->condition_id = $outcome->condition_id === null ? null : ($conditions[$outcome->condition_id] ?? null);
                $copy->priority = $outcome->priority;
                $copy->save();

                $this->copied++;
            }
        }
    }

    /**
     * @param  array<string, string>  $rules
     */
    private function copyReferences(RuleSet $source, array $rules): void
    {
        $references = RuleReference::query()
            ->whereIn('rule_id', array_keys($rules))
            ->get();

        foreach ($references as $reference) {
            $from = $rules[$reference->rule_id] ?? null;
            $to = $rules[$reference->referenced_rule_id] ?? null;

            if ($from === null || $to === null) {
                continue;
            }

            $copy = new RuleReference;
            $copy->rule_id = $from;
            $copy->referenced_rule_id = $to;
            $copy->reference_type = $reference->reference_type;
            $copy->description = $reference->description;
            $copy->save();

            $this->copied++;
        }
    }

    /**
     * Point a freshly written row at its freshly written parent.
     *
     * Saved quietly rather than through a command: this is the second half of one
     * write, and dispatching an "updated" event for it would tell consumers a
     * rule had been reparented when it had only just been created.
     */
    private function setParent(?Model $record, string $column, ?string $parentId): void
    {
        if ($record === null || $parentId === null) {
            return;
        }

        $record->setAttribute($column, $parentId);
        $record->save();
    }
}
