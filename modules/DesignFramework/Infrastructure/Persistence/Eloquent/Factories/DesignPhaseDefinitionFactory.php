<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;

/**
 * @extends Factory<DesignPhaseDefinition>
 */
class DesignPhaseDefinitionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DesignPhaseDefinition>
     */
    protected $model = DesignPhaseDefinition::class;

    /**
     * Define the model's default state.
     *
     * Published by default, unlike the command. Most tests want a phase a designer can see,
     * and the ones about draft content say so — which keeps the common case short without
     * making the visibility rules invisible.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(str_replace('-', ' ', fake()->unique()->slug(2)));

        return [
            'framework_version_id' => FrameworkVersion::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'position' => 1,
            'status' => FrameworkContentStatus::Published,
        ];
    }

    /**
     * Append the phase to a specific edition.
     *
     * The position is read rather than chosen, so a test that builds three phases gets them
     * at 1, 2 and 3 — which is what `ContentSequencer` guarantees in production and what
     * every ordering assertion depends on.
     */
    public function inVersion(FrameworkVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'framework_version_id' => $version->id,
            'position' => $version->phases()->count() + 1,
        ]);
    }

    /**
     * Give the phase a specific name, deriving its address to match.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Give the phase a specific address.
     */
    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Place the phase at a specific point in the arc.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Indicate that the phase is still being written.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FrameworkContentStatus::Draft,
        ]);
    }

    /**
     * Indicate that the phase has been dropped from the methodology.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FrameworkContentStatus::Archived,
        ]);
    }
}
