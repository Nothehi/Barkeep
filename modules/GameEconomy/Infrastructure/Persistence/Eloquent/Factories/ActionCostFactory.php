<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * @extends Factory<ActionCost>
 */
class ActionCostFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ActionCost>
     */
    protected $model = ActionCost::class;

    /**
     * Define the model's default state.
     *
     * The action comes first and the resource is built inside the same profile,
     * so the default is a cost the application layer would have accepted.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action_id' => EconomyAction::factory(),
            'resource_type_id' => function (array $attributes): string {
                $action = EconomyAction::query()->whereKey($attributes['action_id'])->firstOrFail();

                return ResourceType::factory()
                    ->create(['balance_profile_id' => $action->balance_profile_id])->id;
            },
            'amount' => '5.000000',
            'is_variable' => false,
            'min_amount' => null,
            'max_amount' => null,
        ];
    }

    /**
     * Price a specific action in a specific resource.
     *
     * Both together, because the pair sharing a profile is the invariant this
     * module checks on every write.
     */
    public function of(EconomyAction $action, ResourceType $resource, string $amount = '5'): static
    {
        return $this->state(fn (array $attributes) => [
            'action_id' => $action->id,
            'resource_type_id' => $resource->id,
            'amount' => Quantity::from($amount)->toStorage(),
        ]);
    }

    /**
     * Make the cost swing between two amounts.
     */
    public function ranging(string $minimum, string $maximum): static
    {
        return $this->state(fn (array $attributes) => [
            'is_variable' => true,
            'min_amount' => Quantity::from($minimum)->toStorage(),
            'max_amount' => Quantity::from($maximum)->toStorage(),
        ]);
    }
}
