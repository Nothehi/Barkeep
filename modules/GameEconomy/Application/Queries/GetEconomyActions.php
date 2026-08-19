<?php

namespace Modules\GameEconomy\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The actions a configuration declares.
 *
 * Counts rather than contents: an actions list shows "3 costs, 1 reward" per
 * row, and loading every line to render a number would be forty-three queries
 * for a screen that needs three.
 */
final class GetEconomyActions
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * @return Collection<int, EconomyAction>
     */
    public function handle(BalanceProfile $profile): Collection
    {
        return $this->economy->actionsOf($profile);
    }
}
