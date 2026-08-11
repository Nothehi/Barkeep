<?php

namespace Modules\Workspace\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * @extends Factory<WorkspaceMember>
 */
class WorkspaceMemberFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<WorkspaceMember>
     */
    protected $model = WorkspaceMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'role' => WorkspaceRole::Member,
            'joined_at' => now(),
        ];
    }

    /**
     * Put the membership in a specific workspace.
     */
    public function inWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Attach the membership to a specific account.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Give the membership a specific role.
     */
    public function withRole(WorkspaceRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    /**
     * Indicate that the member administers the workspace.
     */
    public function admin(): static
    {
        return $this->withRole(WorkspaceRole::Admin);
    }

    /**
     * Indicate that the member owns the workspace.
     */
    public function owner(): static
    {
        return $this->withRole(WorkspaceRole::Owner);
    }
}
