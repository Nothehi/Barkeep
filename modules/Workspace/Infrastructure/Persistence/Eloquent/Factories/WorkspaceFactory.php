<?php

namespace Modules\Workspace\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Workspace>
     */
    protected $model = Workspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'owner_id' => User::factory(),
            'status' => WorkspaceStatus::Active,
            'archived_at' => null,
        ];
    }

    /**
     * Give the workspace the owner membership every workspace is supposed to
     * have.
     *
     * Building a workspace without it would produce a state the application
     * layer never creates, so tests get it by default rather than having to
     * remember it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Workspace $workspace): void {
            $workspace->members()->firstOrCreate(
                ['user_id' => $workspace->owner_id],
                ['role' => WorkspaceRole::Owner, 'joined_at' => $workspace->created_at ?? now()],
            );
        });
    }

    /**
     * Hand the workspace to a specific account.
     */
    public function ownedBy(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * Give the workspace a specific address.
     */
    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Indicate that the workspace has been retired.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkspaceStatus::Archived,
            'archived_at' => now(),
        ]);
    }

    /**
     * Indicate that the workspace has been suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkspaceStatus::Suspended,
        ]);
    }
}
