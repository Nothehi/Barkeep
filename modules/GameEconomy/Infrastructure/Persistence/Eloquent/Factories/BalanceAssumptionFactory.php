<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<BalanceAssumption>
 */
class BalanceAssumptionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<BalanceAssumption>
     */
    protected $model = BalanceAssumption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'title' => rtrim(fake()->sentence(6), '.'),
            'description' => fake()->paragraph(),
            'category' => AssumptionCategory::Economy,
            'confidence' => AssumptionConfidence::Medium,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Build the assumption inside a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
            'created_by' => $profile->created_by,
        ]);
    }

    /**
     * Say how much the studio believes it.
     */
    public function withConfidence(AssumptionConfidence $confidence): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence' => $confidence,
        ]);
    }

    /**
     * File the assumption under a specific heading.
     */
    public function inCategory(AssumptionCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Attribute the assumption to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
