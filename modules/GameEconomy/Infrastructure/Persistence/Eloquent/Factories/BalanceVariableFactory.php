<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * @extends Factory<BalanceVariable>
 */
class BalanceVariableFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<BalanceVariable>
     */
    protected $model = BalanceVariable::class;

    /**
     * Define the model's default state.
     *
     * Neither reference is set by default, because a variable about nothing in
     * particular is the ordinary case: a victory threshold or a round limit is
     * about the game rather than about anything in the model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'balance_profile_id' => BalanceProfile::factory(),
            'resource_type_id' => null,
            'action_id' => null,
            'name' => $name,
            'slug' => EconomySlug::fromName($name)->value,
            'description' => fake()->sentence(),
            'value' => '10.000000',
            'unit' => null,
            'min_value' => null,
            'max_value' => null,
            'step' => null,
            'category' => BalanceVariableCategory::Other,
        ];
    }

    /**
     * Build the variable inside a specific configuration.
     */
    public function forProfile(BalanceProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $profile->id,
        ]);
    }

    /**
     * Give the variable a specific name, and the handle that follows from it.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => EconomySlug::fromName($name)->value,
        ]);
    }

    /**
     * Set the base value.
     */
    public function valued(string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => Quantity::from($value)->toStorage(),
        ]);
    }

    /**
     * Give the variable the range a designer considers sane.
     */
    public function bounded(?string $minimum, ?string $maximum, ?string $step = null): static
    {
        return $this->state(fn (array $attributes) => [
            'min_value' => Quantity::fromNullable($minimum)?->toStorage(),
            'max_value' => Quantity::fromNullable($maximum)?->toStorage(),
            'step' => Quantity::fromNullable($step)?->toStorage(),
        ]);
    }

    /**
     * File the variable under a specific heading.
     */
    public function inCategory(BalanceVariableCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Point the variable at the resource it is about, taking the profile from it.
     */
    public function about(ResourceType $resource): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $resource->balance_profile_id,
            'resource_type_id' => $resource->id,
        ]);
    }

    /**
     * Point the variable at the action it is about, taking the profile from it.
     */
    public function forAction(EconomyAction $action): static
    {
        return $this->state(fn (array $attributes) => [
            'balance_profile_id' => $action->balance_profile_id,
            'action_id' => $action->id,
        ]);
    }
}
