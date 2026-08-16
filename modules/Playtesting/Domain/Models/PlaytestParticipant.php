<?php

namespace Modules\Playtesting\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories\PlaytestParticipantFactory;

/**
 * Somebody who was at a playtest session.
 *
 * Most of them do not have a Barkeep account, and the model is shaped around
 * that rather than treating it as an edge case. A playtest is run with the
 * people who are available — a partner, three friends, whoever is at the club
 * on a Tuesday — and requiring each of them to sign up would either stop the
 * playtest being recorded or produce a user table full of people who never
 * agreed to be in it.
 *
 * So `user_id` is nullable and `display_name` is not. The name is what the
 * session is read back with; the account, when there is one, is what links a
 * participant to the rest of the platform.
 *
 * Nothing else about the person is stored. No email, no phone: Playtesting has
 * no use for them, and holding contact details about people outside the
 * platform would be a liability taken on for no benefit.
 *
 * @property string $id
 * @property string $session_id
 * @property string|null $user_id
 * @property string $display_name
 * @property PlaytestParticipantRole $role
 * @property CarbonImmutable|null $joined_at
 * @property CarbonImmutable|null $left_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PlaytestSession|null $session
 * @property-read User|null $user
 */
#[Fillable(['display_name'])]
class PlaytestParticipant extends Model
{
    /** @use HasFactory<PlaytestParticipantFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => PlaytestParticipantRole::Player->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PlaytestParticipantRole::class,
            'joined_at' => 'immutable_datetime',
            'left_at' => 'immutable_datetime',
        ];
    }

    /**
     * The session this person was at.
     *
     * @return BelongsTo<PlaytestSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PlaytestSession::class, 'session_id');
    }

    /**
     * The account behind the participant, when there is one.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether this participant has a Barkeep account.
     */
    public function isRegistered(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Determine whether this participant actually played.
     */
    public function isPlaying(): bool
    {
        return $this->role->isPlaying();
    }

    /**
     * Determine whether the participant belongs to the given session.
     *
     * The check behind attributing an observation or a piece of feedback. A
     * participant id arrives in a request body rather than through a route
     * binding, so it is the one identifier here that is not already scoped by
     * resolution.
     */
    public function belongsToSession(PlaytestSession $session): bool
    {
        return $this->session_id === $session->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlaytestParticipantFactory
    {
        return PlaytestParticipantFactory::new();
    }
}
