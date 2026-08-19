<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<BalanceSnapshot>
 */
class BalanceSnapshotFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<BalanceSnapshot>
     */
    protected $model = BalanceSnapshot::class;

    /**
     * Define the model's default state.
     *
     * The payload defaults to an empty configuration rather than to fake data,
     * because a snapshot's contents are produced by one command reading the live
     * tables — a factory inventing a plausible-looking payload would let a test
     * pass against a shape the real writer never produces.
     *
     * Tests that need a populated snapshot build the configuration and take one,
     * which is also how the comparison tests read.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'name' => 'v'.fake()->numberBetween(1, 9).'.'.fake()->numberBetween(0, 9),
            'description' => fake()->sentence(),
            'snapshot_data' => [
                'version' => 1,
                'profile' => [],
                'resources' => [],
                'flows' => [],
                'actions' => [],
                'variables' => [],
            ],
            'created_by' => User::factory(),
        ];
    }

    /**
     * Build the snapshot against a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
            'created_by' => $profile->created_by,
        ]);
    }

    /**
     * Give the snapshot a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Attribute the snapshot to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
