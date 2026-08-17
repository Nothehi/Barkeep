<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<FrameworkVersion>
 */
class FrameworkVersionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<FrameworkVersion>
     */
    protected $model = FrameworkVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'framework_id' => Framework::factory(),
            'version_number' => FrameworkVersionNumber::FIRST,
            'name' => null,
            'description' => fake()->sentence(),
            'status' => FrameworkStatus::Draft,
            'published_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Cut the next edition of a specific framework.
     *
     * Reads the highest existing number rather than taking one, which is the factory
     * honouring the same rule `CreateFrameworkVersion` enforces. A factory that let a test
     * pick a number would let it build two v1s, and the unique index would then fail in a
     * way that looked like a bug in the code under test.
     */
    public function nextFor(Framework $framework): static
    {
        return $this->state(fn (array $attributes) => [
            'framework_id' => $framework->id,
            'version_number' => (int) ($framework->versions()->max('version_number') ?? 0) + 1,
            'created_by' => $framework->created_by,
        ]);
    }

    /**
     * Give the version a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Attribute the version to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Indicate that the version is frozen and adoptable.
     *
     * Sets `published_at` alongside the status, because the command does and a version
     * published without one would let a test pass against data no command could produce.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FrameworkStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the version has been retired.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FrameworkStatus::Archived,
        ]);
    }
}
