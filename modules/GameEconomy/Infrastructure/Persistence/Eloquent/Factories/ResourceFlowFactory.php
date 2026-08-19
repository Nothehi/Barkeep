<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * @extends Factory<ResourceFlow>
 */
class ResourceFlowFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ResourceFlow>
     */
    protected $model = ResourceFlow::class;

    /**
     * Define the model's default state.
     *
     * The resource is built *from* the profile rather than beside it, which is
     * the factory honouring the invariant the database cannot express: a flow
     * and its resource have to share a configuration. A default that made two
     * unrelated records would hand every test a flow the application layer would
     * have refused to create.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'resource_type_id' => fn (array $attributes): string => ResourceType::factory()
                ->create(['balance_profile_id' => $attributes['balance_profile_id']])->id,
            'name' => rtrim(fake()->sentence(2), '.'),
            'description' => fake()->sentence(),
            'flow_type' => ResourceFlowType::Generation,
            'amount' => '3.000000',
            'condition' => 'per round',
            'position' => 0,
        ];
    }

    /**
     * Build the flow for a specific resource, taking the profile from it.
     *
     * The only way to set the resource directly, and it sets the profile to
     * match — because the pair is what the module guarantees, and a factory that
     * let a caller split them would be a way to write data no command could.
     */
    public function forResource(ResourceType $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $resource->balance_profile_id,
            'resource_type_id' => $resource->id,
        ]);
    }

    /**
     * Make it a particular kind of movement.
     */
    public function ofType(ResourceFlowType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'flow_type' => $type,
        ]);
    }

    /**
     * Set how much moves.
     */
    public function amounting(string $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => Quantity::from($amount)->toStorage(),
        ]);
    }

    /**
     * Give the flow a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
