<?php

namespace Modules\Workspace\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Infrastructure\Persistence\Eloquent\Factories\WorkspaceInvitationFactory;

/**
 * A standing offer to join a workspace, addressed to an email address.
 *
 * The invitation is addressed to an address rather than to an account,
 * because the person invited may not have registered yet. Turning an address
 * into an account is Identity's job; this record only says which address was
 * invited and what role it was invited as.
 *
 * Only the token's digest is stored. The plaintext is returned once, at
 * creation, so it can be emailed — it is never readable again.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $email
 * @property WorkspaceRole $role
 * @property string $token_hash
 * @property InvitationStatus $status
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $revoked_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Workspace|null $workspace
 * @property-read User|null $creator
 */
#[Fillable(['email', 'role', 'expires_at', 'created_by'])]
#[Hidden(['token_hash'])]
class WorkspaceInvitation extends Model
{
    /** @use HasFactory<WorkspaceInvitationFactory> */
    use HasFactory, HasUuids;

    /**
     * How long a fresh invitation stays redeemable.
     */
    public const LIFETIME_IN_DAYS = 14;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => InvitationStatus::Pending->value,
        'role' => WorkspaceRole::Member->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * The workspace being joined.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The account that issued the invitation.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Look up an invitation by the token a caller presented.
     *
     * Matching happens on the digest, so the plaintext never reaches the
     * query log. A caller who guesses wrong simply gets nothing back.
     *
     * @param  Builder<WorkspaceInvitation>  $query
     * @return Builder<WorkspaceInvitation>
     */
    public function scopeForToken(Builder $query, InvitationToken $token): Builder
    {
        return $query->where('token_hash', $token->hash());
    }

    /**
     * Restrict to invitations that have not been accepted or revoked.
     *
     * Expiry is not part of this scope: an expired invitation is still stored
     * as pending, and is reported as expired when it is read.
     *
     * @param  Builder<WorkspaceInvitation>  $query
     * @return Builder<WorkspaceInvitation>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', InvitationStatus::Pending);
    }

    /**
     * The invitation's state as of now.
     *
     * Expiry is derived rather than stored so that an invitation is never
     * redeemable merely because no job has swept it up yet.
     */
    public function effectiveStatus(): InvitationStatus
    {
        if ($this->status === InvitationStatus::Pending && $this->hasExpired()) {
            return InvitationStatus::Expired;
        }

        return $this->status;
    }

    /**
     * Determine whether the invitation is past its expiry.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Determine whether the invitation can still be redeemed.
     */
    public function isAcceptable(): bool
    {
        return $this->effectiveStatus()->isAcceptable();
    }

    /**
     * Determine whether the invitation was addressed to the given account.
     */
    public function wasSentTo(User $user): bool
    {
        return hash_equals($this->email, $user->emailAddress()->value);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WorkspaceInvitationFactory
    {
        return WorkspaceInvitationFactory::new();
    }
}
