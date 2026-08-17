<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;

/**
 * @extends Factory<DesignPrinciple>
 */
class DesignPrincipleFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DesignPrinciple>
     */
    protected $model = DesignPrinciple::class;

    /**
     * Define the model's default state.
     *
     * Filed under no phase and published, which is the shortest thing a test can ask for.
     * Content under a phase says so through {@see inPhase()}, because which phase a principle
     * belongs to is usually the thing the test is about.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->sentence(4));

        return [
            'framework_version_id' => FrameworkVersion::factory(),
            'phase_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(),
            'position' => 1,
            'status' => FrameworkContentStatus::Published,
        ];
    }

    /**
     * Append the principle to a version, filed under no phase.
     */
    public function inVersion(FrameworkVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'framework_version_id' => $version->id,
            'phase_id' => null,
            'position' => DesignPrinciple::query()
                ->where('framework_version_id', $version->id)
                ->whereNull('phase_id')
                ->count() + 1,
        ]);
    }

    /**
     * Append the principle to a phase, taking the version from it.
     *
     * The only way to set the phase, and it sets the version to match — because the pair is
     * what the module guarantees, and a factory that let a test split them would be a way to
     * write data no command could.
     */
    public function inPhase(DesignPhaseDefinition $phase): static
    {
        return $this->state(fn (array $attributes) => [
            'framework_version_id' => $phase->framework_version_id,
            'phase_id' => $phase->id,
            'position' => DesignPrinciple::query()->where('phase_id', $phase->id)->count() + 1,
        ]);
    }

    /**
     * Give the principle a specific title, deriving its address to match.
     */
    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
            'slug' => Str::slug($title),
        ]);
    }

    /**
     * Place the principle at a specific point among its siblings.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Indicate that the principle is still being written, so designers cannot see it.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FrameworkContentStatus::Draft,
        ]);
    }

    /**
     * Indicate that the principle has been dropped from the methodology.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FrameworkContentStatus::Archived,
        ]);
    }
}
