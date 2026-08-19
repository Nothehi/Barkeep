<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * @extends Factory<DesignChange>
 */
class DesignChangeFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DesignChange>
     */
    protected $model = DesignChange::class;

    /**
     * Define the model's default state.
     *
     * The reason is populated rather than left blank, because it is required and
     * because a factory that produced reasonless changes would let a test build
     * the one shape of change the module refuses to accept.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iteration_id' => Iteration::factory(),
            'category' => DesignChangeCategory::Other,
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->sentence(),
            'reason' => fake()->sentence(12),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Record the change against a specific cycle.
     */
    public function forIteration(Iteration $iteration): static
    {
        return $this->state(fn (array $attributes) => [
            'iteration_id' => $iteration->id,
            'created_by' => $iteration->created_by,
        ]);
    }

    /**
     * File the change under a specific category.
     */
    public function inCategory(DesignChangeCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Give the change a specific title.
     */
    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Attribute the change to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
