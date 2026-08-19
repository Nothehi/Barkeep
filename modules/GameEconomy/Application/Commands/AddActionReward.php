<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ActionLineData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Exceptions\ActionAlreadyNamesResource;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Have an action pay out a resource.
 *
 * The mirror of {@see AddActionCost}, and separate from it rather than one
 * command with a direction argument. The two produce different rows, fire
 * different halves of the analysis and are edited in different panels; a shared
 * command would be a parameter every caller could get the wrong way round.
 */
final class AddActionReward
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, EconomyAction $action, ActionLineData $data): ActionReward
    {
        $profile = $action->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $resource = $this->catalogue->resourceForAction($action, $data->resourceTypeId ?? '');

        if ($this->economy->findRewardForResource($action, $resource) !== null) {
            throw ActionAlreadyNamesResource::asReward($resource->getKey(), $resource->name);
        }

        $reward = new ActionReward;

        $reward->action_id = $action->getKey();
        $reward->resource_type_id = $resource->getKey();
        $reward->amount = ($data->amount ?? Quantity::zero())->absolute();
        $reward->is_variable = $data->isVariable ?? false;
        $reward->min_amount = $data->minAmount;
        $reward->max_amount = $data->maxAmount;

        $reward->save();

        $reward->setRelation('action', $action);
        $reward->setRelation('resource', $resource);

        event(new EconomyActionUpdated(
            actionId: $action->getKey(),
            profileId: $action->balance_profile_id,
            changedFields: ['rewards'],
        ));

        return $reward;
    }
}
