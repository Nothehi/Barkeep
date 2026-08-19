<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ResourceFlowData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Events\ResourceFlowUpdated;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\Identity\Domain\Models\User;

/**
 * Retune a declared movement of a resource.
 *
 * The resource may be changed as well as the amount — "actually that harvest is
 * clay, not wood" is an ordinary correction — and the replacement is proved to
 * belong to the same profile exactly as it was on the way in.
 */
final class UpdateResourceFlow
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, ResourceFlow $flow, ResourceFlowData $data): ResourceFlow
    {
        $profile = $flow->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($profile !== null && $data->resourceTypeId !== null && $data->resourceTypeId !== $flow->resource_type_id) {
            $resource = $this->catalogue->resourceOf($profile, $data->resourceTypeId);

            $flow->resource_type_id = $resource->getKey();
            $flow->setRelation('resource', $resource);
        }

        if ($data->name !== null) {
            $flow->name = $data->name;
        }

        foreach (['description', 'condition'] as $field) {
            if ($data->sent($field)) {
                $flow->{$field} = $field === 'description' ? $data->description : $data->condition;
            }
        }

        if ($data->flowType !== null) {
            $flow->flow_type = $data->flowType;
        }

        if ($data->amount !== null) {
            $flow->amount = $data->amount->absolute();
        }

        if ($data->position !== null) {
            $flow->position = $data->position;
        }

        $changed = array_keys($flow->getDirty());

        $flow->save();

        if ($changed !== []) {
            event(new ResourceFlowUpdated(
                flowId: $flow->getKey(),
                profileId: $flow->balance_profile_id,
                changedFields: $changed,
            ));
        }

        return $flow;
    }
}
