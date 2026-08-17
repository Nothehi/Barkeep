<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<Framework>
 */
class FrameworkFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Framework>
     */
    protected $model = Framework::class;

    /**
     * Define the model's default state.
     *
     * Draft by default, matching the command: a test that wants a framework designers can
     * see says so, which keeps "drafts are hidden" from being something a test accidentally
     * proves nothing about.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(str_replace('-', ' ', fake()->unique()->slug(3))).' Framework';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'status' => FrameworkStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Give the framework a specific name, deriving its address to match.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Give the framework a specific address.
     */
    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Attribute the framework to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Put the framework at a specific point in its lifecycle.
     */
    public function withStatus(FrameworkStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that designers can see the framework.
     */
    public function published(): static
    {
        return $this->withStatus(FrameworkStatus::Published);
    }

    /**
     * Indicate that the framework has been retired.
     */
    public function archived(): static
    {
        return $this->withStatus(FrameworkStatus::Archived);
    }
}
