<?php

namespace Modules\Workspace\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Infrastructure\Persistence\Eloquent\Factories\WorkspaceMemberFactory;

/**
 * One account's place inside one workspace.
 *
 * Membership is a first class record rather than a pivot table because it
 * carries its own history and role, and because later contexts will want to
 * reference "this person, in this workspace" as a thing in its own right.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $user_id
 * @property WorkspaceRole $role
 * @property CarbonImmutable $joined_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Workspace|null $workspace
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'role', 'joined_at'])]
class WorkspaceMember extends Model
{
    /** @use HasFactory<WorkspaceMemberFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'joined_at' => 'datetime',
        ];
    }

    /**
     * The workspace this membership is in.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The account this membership belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Determine whether this membership carries the owner role.
     */
    public function isOwner(): bool
    {
        return $this->role === WorkspaceRole::Owner;
    }

    /**
     * Determine whether this membership may administer the workspace.
     */
    public function canAdminister(): bool
    {
        return $this->role->atLeast(WorkspaceRole::Admin);
    }

    /**
     * Determine whether this membership belongs to the given account.
     */
    public function belongsToUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WorkspaceMemberFactory
    {
        return WorkspaceMemberFactory::new();
    }
}
