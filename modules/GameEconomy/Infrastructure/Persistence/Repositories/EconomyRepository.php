<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;

/**
 * Every read the module performs against the configuration inside a profile.
 *
 * The rule this file exists to make checkable is the one the database cannot
 * express: a flow, a cost, a reward or a variable may only name a resource
 * belonging to *the same profile* as the thing it hangs off. The foreign keys
 * prove those resources exist; only a query scoped by profile proves they
 * belong here.
 *
 * So every lookup takes the profile and resolves through it. A resource from
 * another configuration is never compared and rejected — it is simply not found,
 * which is the same shape the platform uses for tenancy everywhere else.
 *
 * Ordering is by the designer's own `position` throughout, falling back to the
 * name. Economies have a reading order — raw materials, then currency, then
 * victory points — and sorting that away makes the lists harder to scan than a
 * spreadsheet.
 */
final class EconomyRepository
{
    /**
     * The resources a configuration declares.
     *
     * @return Collection<int, ResourceType>
     */
    public function resourcesOf(BalanceProfile $profile): Collection
    {
        $resources = $profile->resources()
            ->withCount(['flows', 'costs', 'rewards'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $resources->each(fn (ResourceType $resource) => $resource->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's resources by id.
     */
    public function findResourceInProfile(BalanceProfile $profile, string $resourceId): ?ResourceType
    {
        $resource = $profile->resources()->whereKey($resourceId)->first();

        return $resource === null ? null : $resource->setRelation('profile', $profile);
    }

    /**
     * Determine whether a configuration already uses a resource handle.
     */
    public function profileHasResourceSlug(BalanceProfile $profile, EconomySlug $slug, ?string $ignoreId = null): bool
    {
        return $profile->resources()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * How many records price, pay out or measure a resource.
     *
     * The check behind refusing to delete one. It counts rather than asking
     * whether any exist so the refusal can say how much is at stake — deleting a
     * resource eleven actions are priced in would silently make all eleven free.
     */
    public function countUsesOfResource(ResourceType $resource): int
    {
        return $resource->flows()->count()
            + $resource->costs()->count()
            + $resource->rewards()->count()
            + BalanceVariable::query()->where('resource_type_id', $resource->getKey())->count();
    }

    /**
     * The declared movements of a configuration's resources.
     *
     * @return Collection<int, ResourceFlow>
     */
    public function flowsOf(BalanceProfile $profile): Collection
    {
        $flows = $profile->flows()
            ->with('resource')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $flows->each(fn (ResourceFlow $flow) => $flow->setRelation('profile', $profile));
    }

    /**
     * The declared movements of one resource.
     *
     * @return Collection<int, ResourceFlow>
     */
    public function flowsOfResource(ResourceType $resource): Collection
    {
        return $resource->flows()
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find one of a configuration's flows by id.
     */
    public function findFlowInProfile(BalanceProfile $profile, string $flowId): ?ResourceFlow
    {
        $flow = $profile->flows()->whereKey($flowId)->with('resource')->first();

        return $flow === null ? null : $flow->setRelation('profile', $profile);
    }

    /**
     * The actions a configuration declares.
     *
     * @return Collection<int, EconomyAction>
     */
    public function actionsOf(BalanceProfile $profile): Collection
    {
        $actions = $profile->actions()
            ->withCount(['costs', 'rewards', 'effects'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $actions->each(fn (EconomyAction $action) => $action->setRelation('profile', $profile));
    }

    /**
     * The actions of a configuration with everything they do, loaded.
     *
     * What the analysis reads. One query per child table rather than one per
     * action, which matters: a profile with fourteen actions would otherwise
     * cost forty-three queries to analyse.
     *
     * @return Collection<int, EconomyAction>
     */
    public function actionsWithEconomicsOf(BalanceProfile $profile): Collection
    {
        $actions = $profile->actions()
            ->with(['costs.resource', 'rewards.resource', 'effects'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return $actions->each(fn (EconomyAction $action) => $action->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's actions by id.
     */
    public function findActionInProfile(BalanceProfile $profile, string $actionId): ?EconomyAction
    {
        $action = $profile->actions()->whereKey($actionId)->first();

        return $action === null ? null : $action->setRelation('profile', $profile);
    }

    /**
     * Determine whether a configuration already uses an action handle.
     */
    public function profileHasActionSlug(BalanceProfile $profile, EconomySlug $slug, ?string $ignoreId = null): bool
    {
        return $profile->actions()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * What an action takes to perform.
     *
     * @return Collection<int, ActionCost>
     */
    public function costsOf(EconomyAction $action): Collection
    {
        $costs = $action->costs()->with('resource')->get();

        return $costs->each(fn (ActionCost $cost) => $cost->setRelation('action', $action));
    }

    /**
     * Find one of an action's costs by id.
     */
    public function findCostInAction(EconomyAction $action, string $costId): ?ActionCost
    {
        $cost = $action->costs()->whereKey($costId)->with('resource')->first();

        return $cost === null ? null : $cost->setRelation('action', $action);
    }

    /**
     * Find the line on which an action is already priced in a resource.
     *
     * Read before a cost is added, so that a second line for the same resource
     * becomes an error a designer can act on rather than a unique-index
     * violation reaching them as a 500.
     */
    public function findCostForResource(EconomyAction $action, ResourceType $resource): ?ActionCost
    {
        return $action->costs()->where('resource_type_id', $resource->getKey())->first();
    }

    /**
     * What an action pays out.
     *
     * @return Collection<int, ActionReward>
     */
    public function rewardsOf(EconomyAction $action): Collection
    {
        $rewards = $action->rewards()->with('resource')->get();

        return $rewards->each(fn (ActionReward $reward) => $reward->setRelation('action', $action));
    }

    /**
     * Find one of an action's rewards by id.
     */
    public function findRewardInAction(EconomyAction $action, string $rewardId): ?ActionReward
    {
        $reward = $action->rewards()->whereKey($rewardId)->with('resource')->first();

        return $reward === null ? null : $reward->setRelation('action', $action);
    }

    /**
     * Find the line on which an action already pays out a resource.
     */
    public function findRewardForResource(EconomyAction $action, ResourceType $resource): ?ActionReward
    {
        return $action->rewards()->where('resource_type_id', $resource->getKey())->first();
    }

    /**
     * What an action does that is not a quantity of a resource.
     *
     * @return Collection<int, ActionEffect>
     */
    public function effectsOf(EconomyAction $action): Collection
    {
        $effects = $action->effects()->orderBy('created_at')->orderBy('id')->get();

        return $effects->each(fn (ActionEffect $effect) => $effect->setRelation('action', $action));
    }

    /**
     * Find one of an action's effects by id.
     */
    public function findEffectInAction(EconomyAction $action, string $effectId): ?ActionEffect
    {
        $effect = $action->effects()->whereKey($effectId)->first();

        return $effect === null ? null : $effect->setRelation('action', $action);
    }

    /**
     * The numbers a configuration exposes for tuning.
     *
     * Grouped by category and then by name, because the variable table is the
     * screen designers spend the most time on and twenty-seven numbers in one
     * flat list is the spreadsheet this module exists to replace.
     *
     * @return Collection<int, BalanceVariable>
     */
    public function variablesOf(BalanceProfile $profile): Collection
    {
        $variables = $profile->variables()
            ->with(['resource', 'action'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return $variables->each(fn (BalanceVariable $variable) => $variable->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's variables by id.
     */
    public function findVariableInProfile(BalanceProfile $profile, string $variableId): ?BalanceVariable
    {
        $variable = $profile->variables()->whereKey($variableId)->with(['resource', 'action'])->first();

        return $variable === null ? null : $variable->setRelation('profile', $profile);
    }

    /**
     * Determine whether a configuration already uses a variable handle.
     */
    public function profileHasVariableSlug(BalanceProfile $profile, EconomySlug $slug, ?string $ignoreId = null): bool
    {
        return $profile->variables()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * The hypotheticals a configuration is read under.
     *
     * @return Collection<int, BalanceScenario>
     */
    public function scenariosOf(BalanceProfile $profile): Collection
    {
        $scenarios = $profile->scenarios()
            ->with('creator')
            ->withCount('overrides')
            ->orderBy('name')
            ->get();

        return $scenarios->each(fn (BalanceScenario $scenario) => $scenario->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's scenarios by id.
     */
    public function findScenarioInProfile(BalanceProfile $profile, string $scenarioId): ?BalanceScenario
    {
        $scenario = $profile->scenarios()->whereKey($scenarioId)->with('creator')->first();

        return $scenario === null ? null : $scenario->setRelation('profile', $profile);
    }

    /**
     * Determine whether a configuration already carries a scenario by this name.
     */
    public function profileHasScenarioNamed(BalanceProfile $profile, string $name, ?string $ignoreId = null): bool
    {
        return $profile->scenarios()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * The values a scenario states differently, with the numbers they replace.
     *
     * The variable is loaded because an override on its own is meaningless: "15"
     * is only a scenario when you can see that the base is 10.
     *
     * @return Collection<int, ScenarioVariable>
     */
    public function overridesOf(BalanceScenario $scenario): Collection
    {
        $overrides = $scenario->overrides()
            ->with('variable')
            ->get();

        return $overrides
            ->sortBy(fn (ScenarioVariable $override): string => $override->variable->name)
            ->values()
            ->each(fn (ScenarioVariable $override) => $override->setRelation('scenario', $scenario));
    }

    /**
     * Find the value a scenario states for a particular variable.
     */
    public function findOverrideForVariable(BalanceScenario $scenario, BalanceVariable $variable): ?ScenarioVariable
    {
        return $scenario->overrides()
            ->where('balance_variable_id', $variable->getKey())
            ->with('variable')
            ->first();
    }

    /**
     * Find one of a scenario's overrides by id.
     */
    public function findOverrideInScenario(BalanceScenario $scenario, string $overrideId): ?ScenarioVariable
    {
        $override = $scenario->overrides()->whereKey($overrideId)->with('variable')->first();

        return $override === null ? null : $override->setRelation('scenario', $scenario);
    }
}
