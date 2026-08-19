<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<BalanceObservation>
 */
class BalanceObservationFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<BalanceObservation>
     */
    protected $model = BalanceObservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'title' => rtrim(fake()->sentence(5), '.'),
            'observation' => fake()->paragraph(),
            'source_type' => ObservationSourceType::Playtest,
            'source_reference' => null,
            'severity' => ObservationSeverity::Medium,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Build the observation inside a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
            'created_by' => $profile->created_by,
        ]);
    }

    /**
     * Say how badly it reflects on the economy.
     */
    public function withSeverity(ObservationSeverity $severity): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => $severity,
        ]);
    }

    /**
     * Say where the evidence came from.
     */
    public function from(ObservationSourceType $source, ?string $reference = null): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => $source,
            'source_reference' => $reference,
        ]);
    }

    /**
     * Attribute the observation to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
