<?php

namespace Modules\Workspace\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;

/**
 * @extends Factory<WorkspaceInvitation>
 */
class WorkspaceInvitationFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<WorkspaceInvitation>
     */
    protected $model = WorkspaceInvitation::class;

    /**
     * Define the model's default state.
     *
     * The default token is discarded on purpose: the model stores only a
     * digest, so a test that needs to redeem its invitation must mint the
     * token itself and pass it to {@see withToken()}.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = InvitationToken::generate();

        return [
            'workspace_id' => Workspace::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => WorkspaceRole::Member,
            'token_hash' => $token->hash(),
            'status' => InvitationStatus::Pending,
            'expires_at' => now()->addDays(WorkspaceInvitation::LIFETIME_IN_DAYS),
            'accepted_at' => null,
            'revoked_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Issue the invitation for a specific workspace, from its owner.
     */
    public function inWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
            'created_by' => $workspace->owner_id,
        ]);
    }

    /**
     * Address the invitation to a specific email address.
     */
    public function to(string $email): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $email,
        ]);
    }

    /**
     * Invite somebody as an administrator rather than a member.
     */
    public function asAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => WorkspaceRole::Admin,
        ]);
    }

    /**
     * Use a known token so the invitation can be redeemed in a test.
     */
    public function withToken(InvitationToken $token): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => $token->hash(),
        ]);
    }

    /**
     * Indicate that the invitation is past its expiry.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the invitation has already been redeemed.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate that the invitation has been withdrawn.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvitationStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }
}
