<?php

namespace Modules\GameRules\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
use Modules\GameRules\Domain\ValueObjects\RuleSlug;

/**
 * Every read the module performs against the structure inside a rule set.
 *
 * The rule this file exists to make checkable is the one the database cannot
 * express: a transition, a rule reference, a requirement or an effect may only
 * name records belonging to *the same rule set* as the thing it hangs off. The
 * foreign keys prove those records exist; only a query scoped by rule set proves
 * they belong here.
 *
 * So every lookup takes the set and resolves through it. A phase from another
 * rule system is never compared and rejected — it is simply not found, which is
 * the same shape the platform uses for tenancy everywhere else.
 *
 * Ordering is by the designer's own `position` throughout, falling back to the
 * name. For phases that ordering is more than presentation: a turn structure read
 * out of sequence is a different turn structure.
 */
final class RuleStructureRepository
{
    /*
     |----------------------------------------------------------------------
     | Rules
     |----------------------------------------------------------------------
     */

    /**
     * Every rule in a set, flat, in reading order.
     *
     * Flat rather than nested, and one query rather than one per level. The tree
     * is assembled in memory by whoever needs it, which keeps a cycle in the data
     * from making a relation recurse forever — and makes the whole rulebook one
     * round trip.
     *
     * @return Collection<int, GameRule>
     */
    public function rulesOf(RuleSet $ruleSet): Collection
    {
        /*
         * The whole phase row, not a column subset. `GamePhaseResource` words its
         * type and status from enums, and a partial select would hand it nulls to
         * call `->label()` on.
         */
        $rules = $ruleSet->rules()
            ->with('phase')
            ->withCount(['children', 'requirements', 'effects', 'references'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $rules->each(fn (GameRule $rule) => $rule->setRelation('ruleSet', $ruleSet));
    }

    /**
     * Find one of a set's rules by id.
     */
    public function findRuleInRuleSet(RuleSet $ruleSet, string $ruleId): ?GameRule
    {
        $rule = $ruleSet->rules()->whereKey($ruleId)->first();

        return $rule === null ? null : $rule->setRelation('ruleSet', $ruleSet);
    }

    /**
     * Determine whether a set already uses a rule handle.
     */
    public function ruleSetHasRuleSlug(RuleSet $ruleSet, RuleSlug $slug, ?string $ignoreId = null): bool
    {
        return $ruleSet->rules()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * The parent of every rule in a set, as `[childId => parentId|null]`.
     *
     * What the cycle detector walks. A map rather than models, because the check
     * only needs the edges and loading the rules to follow them would be the
     * whole rulebook fetched to answer a question about pointers.
     *
     * @return array<string, string|null>
     */
    public function ruleParentMap(RuleSet $ruleSet): array
    {
        /** @var array<string, string|null> $map */
        $map = $ruleSet->rules()
            ->pluck('parent_rule_id', 'id')
            ->all();

        return $map;
    }

    /**
     * How many rules sit directly under the given parent.
     *
     * What a new rule's default position is taken from, so that a rule created
     * without one lands at the end of its list rather than on top of a sibling.
     */
    public function countRuleChildren(RuleSet $ruleSet, ?string $parentRuleId): int
    {
        return $ruleSet->rules()
            ->when(
                $parentRuleId === null,
                fn (Builder $query) => $query->whereNull('parent_rule_id'),
                fn (Builder $query) => $query->where('parent_rule_id', $parentRuleId),
            )
            ->count();
    }

    /*
     |----------------------------------------------------------------------
     | Mechanics
     |----------------------------------------------------------------------
     */

    /**
     * @return Collection<int, RuleMechanic>
     */
    public function mechanicsOf(RuleSet $ruleSet): Collection
    {
        $mechanics = $ruleSet->mechanics()
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $mechanics->each(fn (RuleMechanic $mechanic) => $mechanic->setRelation('ruleSet', $ruleSet));
    }

    public function findMechanicInRuleSet(RuleSet $ruleSet, string $mechanicId): ?RuleMechanic
    {
        $mechanic = $ruleSet->mechanics()->whereKey($mechanicId)->first();

        return $mechanic === null ? null : $mechanic->setRelation('ruleSet', $ruleSet);
    }

    public function ruleSetHasMechanicSlug(RuleSet $ruleSet, RuleSlug $slug, ?string $ignoreId = null): bool
    {
        return $ruleSet->mechanics()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /*
     |----------------------------------------------------------------------
     | Phases
     |----------------------------------------------------------------------
     */

    /**
     * Every phase in a set, in the order play visits them.
     *
     * @return Collection<int, GamePhase>
     */
    public function phasesOf(RuleSet $ruleSet): Collection
    {
        $phases = $ruleSet->phases()
            ->withCount(['actions', 'rules', 'children'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $phases->each(fn (GamePhase $phase) => $phase->setRelation('ruleSet', $ruleSet));
    }

    public function findPhaseInRuleSet(RuleSet $ruleSet, string $phaseId): ?GamePhase
    {
        $phase = $ruleSet->phases()->whereKey($phaseId)->first();

        return $phase === null ? null : $phase->setRelation('ruleSet', $ruleSet);
    }

    public function ruleSetHasPhaseSlug(RuleSet $ruleSet, RuleSlug $slug, ?string $ignoreId = null): bool
    {
        return $ruleSet->phases()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * The parent of every phase in a set, as `[childId => parentId|null]`.
     *
     * @return array<string, string|null>
     */
    public function phaseParentMap(RuleSet $ruleSet): array
    {
        /** @var array<string, string|null> $map */
        $map = $ruleSet->phases()->pluck('parent_phase_id', 'id')->all();

        return $map;
    }

    public function countPhaseChildren(RuleSet $ruleSet, ?string $parentPhaseId): int
    {
        return $ruleSet->phases()
            ->when(
                $parentPhaseId === null,
                fn (Builder $query) => $query->whereNull('parent_phase_id'),
                fn (Builder $query) => $query->where('parent_phase_id', $parentPhaseId),
            )
            ->count();
    }

    /*
     |----------------------------------------------------------------------
     | Transitions
     |----------------------------------------------------------------------
     */

    /**
     * Every edge in a set's phase graph, with both ends and its guard loaded.
     *
     * One query with eager loads rather than one per edge: the graph builder and
     * the phase designer both read the whole thing, and a set with nine phases
     * has a dozen edges.
     *
     * @return Collection<int, PhaseTransition>
     */
    public function transitionsOf(RuleSet $ruleSet): Collection
    {
        $transitions = $ruleSet->transitions()
            ->with(['fromPhase', 'toPhase', 'condition', 'trigger'])
            ->orderBy('position')
            ->get();

        return $transitions->each(fn (PhaseTransition $transition) => $transition->setRelation('ruleSet', $ruleSet));
    }

    public function findTransitionInRuleSet(RuleSet $ruleSet, string $transitionId): ?PhaseTransition
    {
        $transition = $ruleSet->transitions()->whereKey($transitionId)->first();

        return $transition === null ? null : $transition->setRelation('ruleSet', $ruleSet);
    }

    public function countTransitionsFrom(RuleSet $ruleSet, string $fromPhaseId): int
    {
        return $ruleSet->transitions()->where('from_phase_id', $fromPhaseId)->count();
    }

    /*
     |----------------------------------------------------------------------
     | Actions
     |----------------------------------------------------------------------
     */

    /**
     * The actions a rule set declares, with counts rather than contents.
     *
     * An actions list draws "2 requirements, 3 effects" per row, and loading every
     * line to render a number would be two queries per action for a screen that
     * needs two in total.
     *
     * @return Collection<int, RuleAction>
     */
    public function actionsOf(RuleSet $ruleSet): Collection
    {
        $actions = $ruleSet->actions()
            ->with('phase')
            ->withCount(['requirements', 'effects'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $actions->each(fn (RuleAction $action) => $action->setRelation('ruleSet', $ruleSet));
    }

    public function findActionInRuleSet(RuleSet $ruleSet, string $actionId): ?RuleAction
    {
        $action = $ruleSet->actions()->whereKey($actionId)->with('phase')->first();

        return $action === null ? null : $action->setRelation('ruleSet', $ruleSet);
    }

    public function ruleSetHasActionSlug(RuleSet $ruleSet, RuleSlug $slug, ?string $ignoreId = null): bool
    {
        return $ruleSet->actions()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /*
     |----------------------------------------------------------------------
     | Requirements and effects
     |----------------------------------------------------------------------
     */

    /**
     * @return Collection<int, RuleRequirement>
     */
    public function requirementsOf(RuleSet $ruleSet): Collection
    {
        return $ruleSet->requirements()
            ->orderBy('position')
            ->get();
    }

    public function findRequirementInRuleSet(RuleSet $ruleSet, string $requirementId): ?RuleRequirement
    {
        return $ruleSet->requirements()->whereKey($requirementId)->first();
    }

    public function countRequirementsFor(RuleSet $ruleSet, ?string $ruleId, ?string $actionId): int
    {
        return $ruleSet->requirements()
            ->when($ruleId !== null, fn (Builder $query) => $query->where('rule_id', $ruleId))
            ->when($actionId !== null, fn (Builder $query) => $query->where('action_id', $actionId))
            ->count();
    }

    /**
     * @return Collection<int, RuleEffect>
     */
    public function effectsOf(RuleSet $ruleSet): Collection
    {
        return $ruleSet->effects()
            ->orderBy('position')
            ->get();
    }

    public function findEffectInRuleSet(RuleSet $ruleSet, string $effectId): ?RuleEffect
    {
        return $ruleSet->effects()->whereKey($effectId)->first();
    }

    public function countEffectsFor(RuleSet $ruleSet, ?string $ruleId, ?string $actionId): int
    {
        return $ruleSet->effects()
            ->when($ruleId !== null, fn (Builder $query) => $query->where('rule_id', $ruleId))
            ->when($actionId !== null, fn (Builder $query) => $query->where('action_id', $actionId))
            ->count();
    }

    /*
     |----------------------------------------------------------------------
     | Conditions, groups and triggers
     |----------------------------------------------------------------------
     */

    /**
     * @return Collection<int, RuleCondition>
     */
    public function conditionsOf(RuleSet $ruleSet): Collection
    {
        $conditions = $ruleSet->conditions()
            ->orderBy('name')
            ->get();

        return $conditions->each(fn (RuleCondition $condition) => $condition->setRelation('ruleSet', $ruleSet));
    }

    public function findConditionInRuleSet(RuleSet $ruleSet, string $conditionId): ?RuleCondition
    {
        $condition = $ruleSet->conditions()->whereKey($conditionId)->first();

        return $condition === null ? null : $condition->setRelation('ruleSet', $ruleSet);
    }

    public function ruleSetHasConditionNamed(RuleSet $ruleSet, string $name, ?string $ignoreId = null): bool
    {
        return $this->hasNamed($ruleSet->conditions(), $name, $ignoreId);
    }

    /**
     * @return Collection<int, ConditionGroup>
     */
    public function conditionGroupsOf(RuleSet $ruleSet): Collection
    {
        /*
         * Both relations, and both are needed. `conditions` is what the group
         * reads as; `memberships` is what a client removes one *by*, because the
         * same condition may be in several groups and detaching it from one must
         * not touch the others.
         */
        $groups = $ruleSet->conditionGroups()
            ->with(['conditions', 'memberships'])
            ->withCount('conditions')
            ->orderBy('name')
            ->get();

        return $groups->each(fn (ConditionGroup $group) => $group->setRelation('ruleSet', $ruleSet));
    }

    public function findConditionGroupInRuleSet(RuleSet $ruleSet, string $groupId): ?ConditionGroup
    {
        $group = $ruleSet->conditionGroups()->whereKey($groupId)->with(['conditions', 'memberships'])->first();

        return $group === null ? null : $group->setRelation('ruleSet', $ruleSet);
    }

    public function ruleSetHasConditionGroupNamed(RuleSet $ruleSet, string $name, ?string $ignoreId = null): bool
    {
        return $this->hasNamed($ruleSet->conditionGroups(), $name, $ignoreId);
    }

    /**
     * Find one condition's membership of one group.
     */
    public function findMembership(ConditionGroup $group, string $membershipId): ?ConditionGroupCondition
    {
        return $group->memberships()->whereKey($membershipId)->first();
    }

    public function groupHasCondition(ConditionGroup $group, string $conditionId): bool
    {
        return $group->memberships()->where('condition_id', $conditionId)->exists();
    }

    public function countGroupMembers(ConditionGroup $group): int
    {
        return $group->memberships()->count();
    }

    /**
     * @return Collection<int, RuleTrigger>
     */
    public function triggersOf(RuleSet $ruleSet): Collection
    {
        $triggers = $ruleSet->triggers()
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $triggers->each(fn (RuleTrigger $trigger) => $trigger->setRelation('ruleSet', $ruleSet));
    }

    public function findTriggerInRuleSet(RuleSet $ruleSet, string $triggerId): ?RuleTrigger
    {
        $trigger = $ruleSet->triggers()->whereKey($triggerId)->first();

        return $trigger === null ? null : $trigger->setRelation('ruleSet', $ruleSet);
    }

    public function ruleSetHasTriggerNamed(RuleSet $ruleSet, string $name, ?string $ignoreId = null): bool
    {
        return $this->hasNamed($ruleSet->triggers(), $name, $ignoreId);
    }

    /*
     |----------------------------------------------------------------------
     | Outcomes
     |----------------------------------------------------------------------
     */

    /**
     * @return Collection<int, VictoryCondition>
     */
    public function victoryConditionsOf(RuleSet $ruleSet): Collection
    {
        return $ruleSet->victoryConditions()->with('condition')->orderBy('priority')->orderBy('name')->get();
    }

    public function findVictoryConditionInRuleSet(RuleSet $ruleSet, string $outcomeId): ?VictoryCondition
    {
        return $ruleSet->victoryConditions()->whereKey($outcomeId)->first();
    }

    /**
     * @return Collection<int, DefeatCondition>
     */
    public function defeatConditionsOf(RuleSet $ruleSet): Collection
    {
        return $ruleSet->defeatConditions()->with('condition')->orderBy('priority')->orderBy('name')->get();
    }

    public function findDefeatConditionInRuleSet(RuleSet $ruleSet, string $outcomeId): ?DefeatCondition
    {
        return $ruleSet->defeatConditions()->whereKey($outcomeId)->first();
    }

    /**
     * @return Collection<int, GameEndCondition>
     */
    public function endConditionsOf(RuleSet $ruleSet): Collection
    {
        return $ruleSet->endConditions()->with('condition')->orderBy('priority')->orderBy('name')->get();
    }

    public function findEndConditionInRuleSet(RuleSet $ruleSet, string $outcomeId): ?GameEndCondition
    {
        return $ruleSet->endConditions()->whereKey($outcomeId)->first();
    }

    /**
     * Determine whether a set already carries a victory condition by this name.
     */
    public function ruleSetHasVictoryConditionNamed(RuleSet $ruleSet, string $name, ?string $ignoreId = null): bool
    {
        return $this->hasNamed($ruleSet->victoryConditions(), $name, $ignoreId);
    }

    /**
     * Determine whether a set already carries a defeat condition by this name.
     */
    public function ruleSetHasDefeatConditionNamed(RuleSet $ruleSet, string $name, ?string $ignoreId = null): bool
    {
        return $this->hasNamed($ruleSet->defeatConditions(), $name, $ignoreId);
    }

    /**
     * Determine whether a set already carries an end condition by this name.
     */
    public function ruleSetHasGameEndConditionNamed(RuleSet $ruleSet, string $name, ?string $ignoreId = null): bool
    {
        return $this->hasNamed($ruleSet->endConditions(), $name, $ignoreId);
    }

    /*
     |----------------------------------------------------------------------
     | References
     |----------------------------------------------------------------------
     */

    /**
     * Every reference between rules in a set.
     *
     * Reached through the rules rather than through a column of its own, because
     * `rule_references` deliberately has no `rule_set_id` — the set is knowable
     * from either end and a third copy would be a third thing to keep in step.
     *
     * @return Collection<int, RuleReference>
     */
    public function referencesOf(RuleSet $ruleSet): Collection
    {
        return RuleReference::query()
            ->whereHas('rule', fn (Builder $query) => $query->where('rule_set_id', $ruleSet->getKey()))
            ->with(['rule', 'referencedRule'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one reference, scoped to the set both its rules belong to.
     */
    public function findReferenceInRuleSet(RuleSet $ruleSet, string $referenceId): ?RuleReference
    {
        return RuleReference::query()
            ->whereKey($referenceId)
            ->whereHas('rule', fn (Builder $query) => $query->where('rule_set_id', $ruleSet->getKey()))
            ->first();
    }

    /**
     * The directed edges of a set's reference graph, as `[ruleId => [ruleId, …]]`.
     *
     * What the cycle detector walks. Only the directed kinds are included:
     * "related to" is symmetric and says nothing about which rule comes first, so
     * a mutual one is a note rather than a contradiction.
     *
     * @return array<string, list<string>>
     */
    public function directedReferenceMap(RuleSet $ruleSet): array
    {
        $edges = [];

        foreach ($this->referencesOf($ruleSet) as $reference) {
            if (! $reference->isDirected()) {
                continue;
            }

            $edges[$reference->rule_id][] = $reference->referenced_rule_id;
        }

        return $edges;
    }

    public function referenceExists(string $ruleId, string $referencedRuleId, string $type): bool
    {
        return RuleReference::query()
            ->where('rule_id', $ruleId)
            ->where('referenced_rule_id', $referencedRuleId)
            ->where('reference_type', $type)
            ->exists();
    }

    /**
     * Determine whether a relation already holds a record with this name.
     *
     * Case-insensitive, because "All players passed" and "all players passed"
     * are the same condition to everybody except the database. Six kinds of
     * record are addressed by name rather than by a derived handle — conditions,
     * groups, triggers and the three outcomes — and they share this so the
     * comparison has one definition.
     *
     * @param  HasMany<covariant Model, RuleSet>  $relation
     */
    private function hasNamed(HasMany $relation, string $name, ?string $ignoreId = null): bool
    {
        return $relation
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId !== null, fn (Builder $inner) => $inner->whereKeyNot($ignoreId))
            ->exists();
    }
}
