<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Enums\MechanicCategory;
use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\Models\Mechanic;

/**
 * @extends Factory<Mechanic>
 */
class MechanicFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Mechanic>
     */
    protected $model = Mechanic::class;

    /**
     * Define the model's default state.
     *
     * Published, because that is the only state a designer ever encounters and
     * a factory default of "retired" would hand every test a term nothing may
     * claim.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(str_replace('-', ' ', fake()->unique()->slug(2)));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'category' => MechanicCategory::default(),
            'status' => MechanicStatus::Published,
        ];
    }

    /**
     * Give the mechanic a specific name, deriving its address to match.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * File the mechanic under a category.
     */
    public function inCategory(MechanicCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Indicate that the term has been retired from the vocabulary.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MechanicStatus::Archived,
        ]);
    }
}
