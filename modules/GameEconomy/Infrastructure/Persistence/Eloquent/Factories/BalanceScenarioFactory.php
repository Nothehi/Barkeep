<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<BalanceScenario>
 */
class BalanceScenarioFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<BalanceScenario>
     */
    protected $model = BalanceScenario::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'name' => fake()->unique()->randomElement([
                'Starting player', 'Rich economy', 'Poor economy', 'Fast expansion',
                'Late game', 'Two player', 'Three player', 'Four player',
            ]),
            'description' => fake()->sentence(),
            'status' => BalanceScenarioStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Build the scenario inside a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
            'created_by' => $profile->created_by,
        ]);
    }

    /**
     * Give the scenario a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Put the scenario at a specific point in its lifecycle.
     */
    public function withStatus(BalanceScenarioStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that the scenario is one the studio is currently reading against.
     */
    public function active(): static
    {
        return $this->withStatus(BalanceScenarioStatus::Active);
    }

    /**
     * Indicate that the scenario has been put away.
     */
    public function archived(): static
    {
        return $this->withStatus(BalanceScenarioStatus::Archived);
    }

    /**
     * Attribute the scenario to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
