<?php

namespace Modules\GameEconomy\Infrastructure\Calculations;

use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * Turns a live configuration into the frozen copy a snapshot holds.
 *
 * A copy rather than a set of references, which is the whole point of a
 * snapshot: "what did the economy look like at the convention?" has to stay
 * answerable after the resources it describes have been renamed, retyped or
 * deleted. References would give an answer that changed every time somebody
 * edited the present.
 *
 * ## Identity in the payload
 *
 * Records carry their slug as well as their id, and the comparison matches on
 * the slug. That is deliberate: a resource deleted and recreated under the same
 * name is, to somebody asking what changed between two snapshots, the same
 * resource — and matching on ids would report it as a removal and an addition,
 * burying the actual change in noise.
 *
 * Costs, rewards and effects are nested inside their action rather than listed
 * flat, because that is how they are read: nobody asks "what changed about
 * cost 47", they ask "what changed about Build".
 *
 * ## The version field
 *
 * The payload states its own shape version. Snapshots are immutable and outlive
 * schema changes by design, so a reader written a year from now has to be able
 * to tell what it is looking at — and the alternative, migrating stored
 * snapshots, is exactly what "history is immutable" forbids.
 */
final class SnapshotWriter
{
    /**
     * The shape version this writer produces.
     *
     * Bumped when the payload's structure changes in a way a reader has to know
     * about. Existing snapshots keep the version they were written with.
     */
    public const SHAPE_VERSION = 1;

    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * Freeze everything about a configuration.
     *
     * Amounts are written as their storage representation rather than as
     * whatever the interface displays, so a snapshot round-trips through JSON at
     * full precision. A payload storing "3" instead of "3.000000" would compare
     * unequal to a live value that had not changed.
     *
     * @return array<string, mixed>
     */
    public function capture(BalanceProfile $profile): array
    {
        return [
            'version' => self::SHAPE_VERSION,
            'profile' => [
                'id' => $profile->getKey(),
                'name' => $profile->name,
                'description' => $profile->description,
                'status' => $profile->status->value,
                'game_version_id' => $profile->game_version_id,
            ],
            'resources' => $this->resources($profile),
            'flows' => $this->flows($profile),
            'actions' => $this->actions($profile),
            'variables' => $this->variables($profile),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resources(BalanceProfile $profile): array
    {
        $captured = [];

        foreach ($this->economy->resourcesOf($profile) as $resource) {
            $captured[] = [
                'id' => $resource->id,
                'slug' => $resource->slug,
                'name' => $resource->name,
                'category' => $resource->category->value,
                'description' => $resource->description,
                'unit' => $resource->unit,
                'is_tradeable' => $resource->is_tradeable,
                'is_accumulative' => $resource->is_accumulative,
                'is_spendable' => $resource->is_spendable,
                'is_convertible' => $resource->is_convertible,
                'min_value' => $resource->min_value?->toStorage(),
                'max_value' => $resource->max_value?->toStorage(),
                'starting_value' => $resource->starting_value?->toStorage(),
                'position' => $resource->position,
            ];
        }

        return $captured;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function flows(BalanceProfile $profile): array
    {
        $captured = [];

        foreach ($this->economy->flowsOf($profile) as $flow) {
            $captured[] = [
                'id' => $flow->id,
                'resource_slug' => $flow->resource->slug,
                'resource_name' => $flow->resource->name,
                'name' => $flow->name,
                'description' => $flow->description,
                'flow_type' => $flow->flow_type->value,
                'amount' => $flow->amount->toStorage(),
                'condition' => $flow->condition,
                'position' => $flow->position,
            ];
        }

        return $captured;
    }

    /**
     * Freeze the actions, with everything they do nested inside them.
     *
     * Nested rather than listed flat, because that is how they are read: nobody
     * asks what changed about cost 47, they ask what changed about Build.
     *
     * @return list<array<string, mixed>>
     */
    private function actions(BalanceProfile $profile): array
    {
        $captured = [];

        foreach ($this->economy->actionsWithEconomicsOf($profile) as $action) {
            $costs = [];

            foreach ($action->costs as $cost) {
                $costs[] = [
                    'id' => $cost->id,
                    'resource_slug' => $cost->resource->slug,
                    'resource_name' => $cost->resource->name,
                    'amount' => $cost->amount->toStorage(),
                    'is_variable' => $cost->is_variable,
                    'min_amount' => $cost->min_amount?->toStorage(),
                    'max_amount' => $cost->max_amount?->toStorage(),
                ];
            }

            $rewards = [];

            foreach ($action->rewards as $reward) {
                $rewards[] = [
                    'id' => $reward->id,
                    'resource_slug' => $reward->resource->slug,
                    'resource_name' => $reward->resource->name,
                    'amount' => $reward->amount->toStorage(),
                    'is_variable' => $reward->is_variable,
                    'min_amount' => $reward->min_amount?->toStorage(),
                    'max_amount' => $reward->max_amount?->toStorage(),
                ];
            }

            $effects = [];

            foreach ($action->effects as $effect) {
                $effects[] = [
                    'id' => $effect->id,
                    'effect_type' => $effect->effect_type->value,
                    'target' => $effect->target,
                    'value' => $effect->value?->toStorage(),
                    'description' => $effect->description,
                ];
            }

            $captured[] = [
                'id' => $action->id,
                'slug' => $action->slug,
                'name' => $action->name,
                'description' => $action->description,
                'position' => $action->position,
                'costs' => $costs,
                'rewards' => $rewards,
                'effects' => $effects,
            ];
        }

        return $captured;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function variables(BalanceProfile $profile): array
    {
        $captured = [];

        foreach ($this->economy->variablesOf($profile) as $variable) {
            $captured[] = [
                'id' => $variable->id,
                'slug' => $variable->slug,
                'name' => $variable->name,
                'description' => $variable->description,
                'value' => $variable->value->toStorage(),
                'unit' => $variable->unit,
                'min_value' => $variable->min_value?->toStorage(),
                'max_value' => $variable->max_value?->toStorage(),
                'step' => $variable->step?->toStorage(),
                'category' => $variable->category->value,
                'resource_slug' => $variable->resource?->slug,
                'action_slug' => $variable->action?->slug,
            ];
        }

        return $captured;
    }
}
