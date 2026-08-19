<?php

namespace Modules\GameEconomy\Infrastructure\Calculations;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\ActionProfitability;
use Modules\GameEconomy\Domain\ValueObjects\ConversionRatio;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Domain\ValueObjects\ResourceDelta;
use Modules\GameEconomy\Domain\ValueObjects\ResourceNetFlow;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The arithmetic of a balance configuration.
 *
 * Four questions, and deliberately only four: what enters and leaves for each
 * resource, what an action costs and pays, what one resource buys of another,
 * and whether the numbers sit inside the ranges declared for them.
 *
 * What this is not is the point. It does not simulate turns, search for optimal
 * play, solve for equilibrium or predict a win rate — section 52 of the brief
 * rules all of that out, and the reason is that every one of those requires
 * assumptions about how people play, which is exactly the thing playtesting
 * exists to find out. A calculator that guessed would produce numbers a studio
 * would trust more than their own table.
 *
 * Everything here is pure arithmetic over records that have already been loaded.
 * It writes nothing — section 31 — and it is deterministic, so the same
 * configuration gives the same answers every time.
 *
 * ## What counts as generation and consumption
 *
 * Both the declared flows and the actions contribute. An action that spends five
 * wood removes wood from the economy whether or not anybody also wrote a
 * consumption flow for it, and a module that counted only the flows would report
 * a resource as untouched while fourteen actions were priced in it.
 *
 * Which side each flow lands on comes from the flow type's own `direction()`, so
 * a flow type added later is counted correctly here without this class changing.
 */
final class BalanceCalculator
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * What enters and leaves for every resource in a configuration.
     *
     * Keyed by resource id, and includes resources nothing touches — a resource
     * with no flows is the single most important thing the analysis has to
     * report, so dropping it from the result would hide it.
     *
     * @return array<string, ResourceNetFlow>
     */
    public function netFlows(BalanceProfile $profile): array
    {
        return $this->computeNetFlows(
            $this->economy->resourcesOf($profile),
            $this->economy->flowsOf($profile),
            $this->economy->actionsWithEconomicsOf($profile),
        );
    }

    /**
     * What enters and leaves for one resource.
     */
    public function netFlowFor(BalanceProfile $profile, ResourceType $resource): ResourceNetFlow
    {
        return $this->netFlows($profile)[$resource->getKey()]
            ?? ResourceNetFlow::still($resource->getKey(), $resource->name);
    }

    /**
     * What every action in a configuration does to a player's holdings.
     *
     * @return array<string, ActionProfitability>
     */
    public function profitability(BalanceProfile $profile): array
    {
        $result = [];

        foreach ($this->economy->actionsWithEconomicsOf($profile) as $action) {
            $result[$action->getKey()] = $this->profitabilityOf($action);
        }

        return $result;
    }

    /**
     * What one action does to a player's holdings.
     *
     * Reported per resource and never totalled — see {@see ActionProfitability},
     * which has no field a total could live in precisely so that nothing
     * downstream can render one.
     *
     * Relies on the action's costs, rewards and effects being loaded. Callers
     * that have not loaded them get lazy reads rather than wrong answers, which
     * is why the profile-wide method above loads them all in one pass.
     */
    public function profitabilityOf(EconomyAction $action): ActionProfitability
    {
        /** @var array<string, array{resource: ResourceType, cost: Quantity, reward: Quantity}> $byResource */
        $byResource = [];

        foreach ($action->costs as $cost) {
            $key = $cost->resource_type_id;
            $byResource[$key] ??= ['resource' => $cost->resource, 'cost' => Quantity::zero(), 'reward' => Quantity::zero()];
            $byResource[$key]['cost'] = $byResource[$key]['cost']->plus($cost->effectiveAmount());
        }

        foreach ($action->rewards as $reward) {
            $key = $reward->resource_type_id;
            $byResource[$key] ??= ['resource' => $reward->resource, 'cost' => Quantity::zero(), 'reward' => Quantity::zero()];
            $byResource[$key]['reward'] = $byResource[$key]['reward']->plus($reward->effectiveAmount());
        }

        $deltas = [];

        foreach ($byResource as $resourceId => $entry) {
            $deltas[] = new ResourceDelta(
                resourceId: $resourceId,
                resourceName: $entry['resource']->name,
                unit: $entry['resource']->unit,
                cost: $entry['cost'],
                reward: $entry['reward'],
            );
        }

        return new ActionProfitability(
            actionId: $action->getKey(),
            actionName: $action->name,
            deltas: $deltas,
            effectCount: $action->effects->count(),
        );
    }

    /**
     * Every exchange rate a configuration's actions imply.
     *
     * @return list<ConversionRatio>
     */
    public function conversions(BalanceProfile $profile): array
    {
        $ratios = [];

        foreach ($this->economy->actionsWithEconomicsOf($profile) as $action) {
            foreach ($this->conversionsOf($action) as $ratio) {
                $ratios[] = $ratio;
            }
        }

        return $ratios;
    }

    /**
     * The exchange rates one action implies.
     *
     * One per cost-and-reward pair, which is the only honest reading: an action
     * costing 2 wood and 1 stone and paying 1 gold does not tell you what wood
     * is worth in gold on its own, so both pairs are reported and neither is
     * combined into a single rate.
     *
     * A resource that appears on both sides is skipped. That is not a
     * conversion — it is an action that multiplies a resource, which the
     * analysis reports as a separate and much more interesting finding.
     *
     * @return list<ConversionRatio>
     */
    public function conversionsOf(EconomyAction $action): array
    {
        $ratios = [];

        foreach ($action->costs as $cost) {
            if (! $cost->effectiveAmount()->isPositive()) {
                continue;
            }

            foreach ($action->rewards as $reward) {
                if ($reward->resource_type_id === $cost->resource_type_id) {
                    continue;
                }

                if (! $reward->effectiveAmount()->isPositive()) {
                    continue;
                }

                $ratios[] = ConversionRatio::between(
                    actionId: $action->getKey(),
                    actionName: $action->name,
                    fromResourceId: $cost->resource_type_id,
                    fromResourceName: $cost->resource->name,
                    fromAmount: $cost->effectiveAmount(),
                    toResourceId: $reward->resource_type_id,
                    toResourceName: $reward->resource->name,
                    toAmount: $reward->effectiveAmount(),
                );
            }
        }

        return $ratios;
    }

    /**
     * Do the counting.
     *
     * Separated from the public entry points so the analysis can hand in records
     * it has already loaded rather than making this read them a second time — a
     * profile's resources, flows and actions are each read once per analysis.
     *
     * @param  Collection<int, ResourceType>  $resources
     * @param  Collection<int, ResourceFlow>  $flows
     * @param  Collection<int, EconomyAction>  $actions
     * @return array<string, ResourceNetFlow>
     */
    public function computeNetFlows(Collection $resources, Collection $flows, Collection $actions): array
    {
        /** @var array<string, array{name: string, generation: Quantity, consumption: Quantity}> $totals */
        $totals = [];

        foreach ($resources as $resource) {
            $totals[$resource->getKey()] = [
                'name' => $resource->name,
                'generation' => Quantity::zero(),
                'consumption' => Quantity::zero(),
            ];
        }

        foreach ($flows as $flow) {
            $key = $flow->resource_type_id;

            if (! isset($totals[$key])) {
                continue;
            }

            if ($flow->generates()) {
                $totals[$key]['generation'] = $totals[$key]['generation']->plus($flow->amount);
            } elseif ($flow->consumes()) {
                $totals[$key]['consumption'] = $totals[$key]['consumption']->plus($flow->amount);
            }
        }

        foreach ($actions as $action) {
            foreach ($action->costs as $cost) {
                $this->addTo($totals, $cost->resource_type_id, 'consumption', $cost->effectiveAmount());
            }

            foreach ($action->rewards as $reward) {
                $this->addTo($totals, $reward->resource_type_id, 'generation', $reward->effectiveAmount());
            }
        }

        $result = [];

        foreach ($totals as $resourceId => $entry) {
            $result[$resourceId] = new ResourceNetFlow(
                resourceId: $resourceId,
                resourceName: $entry['name'],
                generation: $entry['generation'],
                consumption: $entry['consumption'],
            );
        }

        return $result;
    }

    /**
     * Add an amount to one side of one resource's tally.
     *
     * A resource that is not in the tally is skipped rather than created. The
     * only way that happens is a cost or reward pointing at a resource from
     * another profile, which the application layer refuses on the way in — so
     * silently ignoring it here is the safe reading of data that should not
     * exist, rather than a way for it to influence a figure.
     *
     * @param  array<string, array{name: string, generation: Quantity, consumption: Quantity}>  $totals
     * @param  'generation'|'consumption'  $side
     */
    private function addTo(array &$totals, string $resourceId, string $side, Quantity $amount): void
    {
        if (! isset($totals[$resourceId])) {
            return;
        }

        $totals[$resourceId][$side] = $totals[$resourceId][$side]->plus($amount);
    }
}
