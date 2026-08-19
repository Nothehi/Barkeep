<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;

/**
 * @extends Factory<EconomyAction>
 */
class EconomyActionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<EconomyAction>
     */
    protected $model = EconomyAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Build', 'Harvest', 'Trade', 'Upgrade', 'Attack', 'Rest', 'Draw Card',
            'Place Worker', 'Sell Resource', 'Research', 'Explore', 'Recruit',
        ]);

        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'name' => $name,
            'slug' => EconomySlug::fromName($name)->value,
            'description' => fake()->sentence(),
            'position' => 0,
        ];
    }

    /**
     * Build the action inside a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
        ]);
    }

    /**
     * Give the action a specific name, and the handle that follows from it.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => EconomySlug::fromName($name)->value,
        ]);
    }

    /**
     * Place the action at a specific point in the designer's ordering.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
