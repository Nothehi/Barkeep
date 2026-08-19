<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ResourceFlowData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\Events\ResourceFlowCreated;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Declare a way a resource moves.
 *
 * The resource id arrives in the request body and is resolved *through* the
 * profile, which is the invariant the database cannot express: nothing in the
 * schema says a flow and the resource it moves share a configuration. A resource
 * from a different profile is never found rather than being found and rejected.
 *
 * The amount is stored as a magnitude regardless of what was sent. Direction
 * belongs to the flow type, and a stored "-2 generation" would be a row that
 * contradicts itself — after which the net calculation has to guess which half
 * the designer meant.
 */
final class CreateResourceFlow
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceProfile $profile, ResourceFlowData $data): ResourceFlow
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $resource = $this->catalogue->resourceOf($profile, $data->resourceTypeId ?? '');

        $flow = new ResourceFlow;

        $flow->fill([
            'name' => $data->name ?? '',
            'description' => $data->description,
            'condition' => $data->condition,
        ]);

        $flow->balance_profile_id = $profile->getKey();
        $flow->resource_type_id = $resource->getKey();
        $flow->flow_type = $data->flowType ?? ResourceFlowType::default();
        $flow->amount = ($data->amount ?? Quantity::zero())->absolute();
        $flow->position = $data->position ?? $this->economy->flowsOf($profile)->count();

        $flow->save();

        $flow->setRelation('profile', $profile);
        $flow->setRelation('resource', $resource);

        event(new ResourceFlowCreated(
            flowId: $flow->id,
            profileId: $profile->getKey(),
            resourceId: $resource->getKey(),
            flowType: $flow->flow_type,
        ));

        return $flow;
    }
}
