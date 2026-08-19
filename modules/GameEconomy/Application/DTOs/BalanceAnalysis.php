<?php

namespace Modules\GameEconomy\Application\DTOs;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\ActionProfitability;
use Modules\GameEconomy\Domain\ValueObjects\BalanceSummary;
use Modules\GameEconomy\Domain\ValueObjects\BalanceWarning;
use Modules\GameEconomy\Domain\ValueObjects\ConversionRatio;
use Modules\GameEconomy\Domain\ValueObjects\ResourceNetFlow;

/**
 * A complete reading of a balance configuration, as of right now.
 *
 * Nothing here is persisted — section 28 — and that is a decision rather than an
 * omission. An analysis is a reading of the configuration as it stands, and
 * storing one would immediately create a second question the module would then
 * have to keep answering: is this still true? Recomputing is cheap and always
 * right.
 *
 * The configuration travels with the findings rather than being fetched again by
 * whoever renders them. An analysis screen shows the warning and the resource it
 * is about side by side, and a second read to resolve the ids would be a second
 * chance for the two halves to disagree.
 */
final readonly class BalanceAnalysis
{
    /**
     * @param  Collection<int, ResourceType>  $resources
     * @param  Collection<int, ResourceFlow>  $flows
     * @param  Collection<int, EconomyAction>  $actions
     * @param  Collection<int, BalanceVariable>  $variables
     * @param  array<string, ResourceNetFlow>  $netFlows
     * @param  array<string, ActionProfitability>  $profitability
     * @param  list<ConversionRatio>  $conversions
     * @param  list<BalanceWarning>  $warnings
     */
    public function __construct(
        public BalanceProfile $profile,
        public Collection $resources,
        public Collection $flows,
        public Collection $actions,
        public Collection $variables,
        public array $netFlows,
        public array $profitability,
        public array $conversions,
        public array $warnings,
        public BalanceSummary $summary,
    ) {}

    /**
     * The findings that describe something that cannot work.
     *
     * @return list<BalanceWarning>
     */
    public function errors(): array
    {
        return array_values(array_filter($this->warnings, fn (BalanceWarning $warning): bool => $warning->isError()));
    }

    /**
     * The findings that describe something a designer might have meant.
     *
     * @return list<BalanceWarning>
     */
    public function advisories(): array
    {
        return array_values(array_filter($this->warnings, fn (BalanceWarning $warning): bool => ! $warning->isError()));
    }

    /**
     * What a resource does on balance.
     */
    public function netFlowFor(ResourceType $resource): ?ResourceNetFlow
    {
        return $this->netFlows[$resource->getKey()] ?? null;
    }

    /**
     * What an action does to a player's holdings.
     */
    public function profitabilityFor(EconomyAction $action): ?ActionProfitability
    {
        return $this->profitability[$action->getKey()] ?? null;
    }
}
