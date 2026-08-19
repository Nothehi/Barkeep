<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * @extends Factory<ResourceType>
 */
class ResourceTypeFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ResourceType>
     */
    protected $model = ResourceType::class;

    /**
     * Define the model's default state.
     *
     * The name is drawn from a small vocabulary of real board-game resources
     * rather than from `fake()->word()`, because the slug is derived from it and
     * has to be unique within a profile — and because a test failure that reads
     * "wood" is easier to follow than one that reads "voluptatem".
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Wood', 'Stone', 'Gold', 'Food', 'Energy', 'Clay', 'Iron', 'Wheat',
            'Influence', 'Mana', 'Cards', 'Workers', 'Victory Points', 'Coal',
        ]);

        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'name' => $name,
            'slug' => EconomySlug::fromName($name)->value,
            'category' => ResourceCategory::Material,
            'description' => fake()->sentence(),
            'unit' => null,
            'is_tradeable' => true,
            'is_accumulative' => true,
            'is_spendable' => true,
            'is_convertible' => false,
            'min_value' => null,
            'max_value' => null,
            'starting_value' => null,
            'position' => 0,
        ];
    }

    /**
     * Build the resource inside a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
        ]);
    }

    /**
     * Give the resource a specific name, and the handle that follows from it.
     *
     * The two are set together because they are set together everywhere else —
     * a factory that let them diverge would be a way to write a resource whose
     * handle does not match its name, which no command can produce.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => EconomySlug::fromName($name)->value,
        ]);
    }

    /**
     * File the resource under a specific heading.
     */
    public function inCategory(ResourceCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Give the resource bounds and a starting amount.
     */
    public function bounded(?string $minimum, ?string $maximum, ?string $starting = null): static
    {
        return $this->state(fn (array $attributes) => [
            'min_value' => Quantity::fromNullable($minimum)?->toStorage(),
            'max_value' => Quantity::fromNullable($maximum)?->toStorage(),
            'starting_value' => Quantity::fromNullable($starting)?->toStorage(),
        ]);
    }

    /**
     * Make it something spent rather than held: an action point.
     */
    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_tradeable' => false,
            'is_accumulative' => false,
            'is_spendable' => true,
        ]);
    }

    /**
     * Place the resource at a specific point in the designer's ordering.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
