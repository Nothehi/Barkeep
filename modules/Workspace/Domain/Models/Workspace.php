<?php

namespace Modules\Workspace\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;
use Modules\Workspace\Infrastructure\Persistence\Eloquent\Factories\WorkspaceFactory;

/**
 * The collaborative boundary users create board-game projects inside.
 *
 * Workspace is the tenancy root of the platform: games, playtests and content
 * will all be owned by a workspace rather than by a user, so that handing a
 * project to a team never requires migrating ownership rows.
 *
 * The workspace knows *that* it has an owner, by account id. It stores nothing
 * about who that account is — Identity owns that.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $owner_id
 * @property WorkspaceStatus $status
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $owner
 * @property-read Collection<int, WorkspaceMember> $members
 * @property-read Collection<int, WorkspaceInvitation> $invitations
 */
#[Fillable(['name', 'slug', 'description'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids;

    /**
     * The route key used in human facing URLs.
     *
     * Workspaces are addressed by slug so that links are readable and
     * shareable; the uuid never appears in a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => WorkspaceStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkspaceStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * The account that owns the workspace.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Everyone who belongs to the workspace, owner included.
     *
     * @return HasMany<WorkspaceMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * Every invitation ever issued for the workspace.
     *
     * @return HasMany<WorkspaceInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /**
     * Get the workspace's address as a value object.
     */
    public function slug(): WorkspaceSlug
    {
        return WorkspaceSlug::fromString($this->slug);
    }

    /**
     * Memoised membership lookups for this instance.
     *
     * Authorization asks "what is this person's role here?" once per ability
     * checked, and a workspace resource reports nine of them. The cache is a
     * plain property on the model rather than state held by the policy, so it
     * lives exactly as long as the object it describes: a fresh instance —
     * the next request's route binding, or a `fresh()` call — starts empty,
     * and nothing can be answered from a previous request's reading.
     *
     * @var array<string, WorkspaceMember|null>
     */
    protected array $resolvedMemberships = [];

    /**
     * Find the given account's membership record, if there is one.
     */
    public function memberFor(User $user): ?WorkspaceMember
    {
        return $this->resolvedMemberships[$user->getKey()] ??= $this->members()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Seed the memo with a membership that has already been read.
     *
     * Lets a caller that has fetched many workspaces resolve everybody's
     * membership in one query instead of one per workspace. Passing null
     * records "this account is not a member", which is just as worth
     * remembering as the positive answer.
     */
    public function rememberMembership(User $user, ?WorkspaceMember $member): void
    {
        $this->resolvedMemberships[$user->getKey()] = $member;
    }

    /**
     * Discard the memoised memberships.
     *
     * Called by the commands that move somebody between roles, so that
     * whatever the request renders afterwards reflects the change rather than
     * the reading taken before it.
     */
    public function forgetResolvedMemberships(): void
    {
        $this->resolvedMemberships = [];
    }

    /**
     * Determine whether the given account belongs to the workspace.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the given account owns the workspace.
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * The membership record that carries the owner role.
     *
     * Kept consistent with {@see $owner_id} by the application layer, which is
     * the only place ownership is allowed to move.
     */
    public function ownerMembership(): ?WorkspaceMember
    {
        return $this->members()
            ->where('role', WorkspaceRole::Owner)
            ->first();
    }

    /**
     * Determine whether the workspace may still be changed.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WorkspaceFactory
    {
        return WorkspaceFactory::new();
    }
}
