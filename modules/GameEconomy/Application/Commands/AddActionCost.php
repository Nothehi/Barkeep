<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ActionLineData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Exceptions\ActionAlreadyNamesResource;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Price an action in a resource.
 *
 * The resource is resolved through the action's own profile, so an action cannot
 * be priced in somebody else's wood. That check is the reason this command
 * exists rather than the controller writing a row: the foreign key proves the
 * resource exists, and only this proves it belongs here.
 *
 * A second line for a resource the action already costs is refused rather than
 * merged. Merging would silently turn "2 wood" plus a mistyped "3 wood" into
 * five, which is the sort of quiet arithmetic a balance tool must never do on a
 * designer's behalf.
 */
final class AddActionCost
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, EconomyAction $action, ActionLineData $data): ActionCost
    {
        $profile = $action->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $resource = $this->catalogue->resourceForAction($action, $data->resourceTypeId ?? '');

        if ($this->economy->findCostForResource($action, $resource) !== null) {
            throw ActionAlreadyNamesResource::asCost($resource->getKey(), $resource->name);
        }

        $cost = new ActionCost;

        $cost->action_id = $action->getKey();
        $cost->resource_type_id = $resource->getKey();
        $cost->amount = ($data->amount ?? Quantity::zero())->absolute();
        $cost->is_variable = $data->isVariable ?? false;
        $cost->min_amount = $data->minAmount;
        $cost->max_amount = $data->maxAmount;

        $cost->save();

        $cost->setRelation('action', $action);
        $cost->setRelation('resource', $resource);

        event(new EconomyActionUpdated(
            actionId: $action->getKey(),
            profileId: $action->balance_profile_id,
            changedFields: ['costs'],
        ));

        return $cost;
    }
}
