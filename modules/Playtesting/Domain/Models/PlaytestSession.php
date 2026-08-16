<?php

namespace Modules\Playtesting\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\ValueObjects\SessionDuration;
use Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories\PlaytestSessionFactory;

/**
 * One sitting of a playtest: a group of people, a table, an evening.
 *
 * A playtest asks a question; a session is one attempt at answering it. Having
 * several is the normal case rather than the exception — the same hypothesis
 * tested against four different groups is how a designer finds out whether the
 * first group was unusual.
 *
 * Everything that makes a session evidence hangs off it: who was there, what
 * was noticed, what they said afterwards. Which is why completing one closes
 * it to all three. A record that stays open to additions after the fact is a
 * record nobody can date.
 *
 * @property string $id
 * @property string $playtest_id
 * @property PlaytestSessionStatus $status
 * @property CarbonImmutable|null $planned_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $ended_at
 * @property string|null $location
 * @property string|null $notes
 * @property string|null $outcome
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Playtest|null $playtest
 * @property-read User|null $creator
 * @property-read Collection<int, PlaytestParticipant> $participants
 * @property-read Collection<int, PlaytestObservation> $observations
 * @property-read Collection<int, PlaytestFeedback> $feedback
 * @property-read int|null $participants_count
 * @property-read int|null $observations_count
 * @property-read int|null $feedback_count
 */
#[Fillable(['location', 'notes', 'outcome'])]
class PlaytestSession extends Model
{
    /** @use HasFactory<PlaytestSessionFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PlaytestSessionStatus::Planned->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PlaytestSessionStatus::class,
            'planned_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    /**
     * The investigation this is a sitting of.
     *
     * @return BelongsTo<Playtest, $this>
     */
    public function playtest(): BelongsTo
    {
        return $this->belongsTo(Playtest::class);
    }

    /**
     * The account that scheduled the session.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Everybody who was at the table, players and otherwise.
     *
     * @return HasMany<PlaytestParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(PlaytestParticipant::class, 'session_id');
    }

    /**
     * What the designers noticed.
     *
     * @return HasMany<PlaytestObservation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(PlaytestObservation::class, 'session_id');
    }

    /**
     * What the participants said.
     *
     * @return HasMany<PlaytestFeedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(PlaytestFeedback::class, 'session_id');
    }

    /**
     * How long the session actually ran, if it ran at all.
     *
     * Derived rather than stored. A session that is still going, or that never
     * started, has no duration — which is different from a duration of zero,
     * and the difference matters when these are averaged.
     */
    public function duration(): ?SessionDuration
    {
        return SessionDuration::between($this->started_at, $this->ended_at);
    }

    /**
     * Determine whether the session's own details may still be changed.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether participants, observations and feedback may still be
     * added to the session.
     */
    public function acceptsEvidence(): bool
    {
        return $this->status->allowsEvidence();
    }

    /**
     * Determine whether the session is under way right now.
     */
    public function isRunning(): bool
    {
        return $this->status === PlaytestSessionStatus::InProgress;
    }

    /**
     * Determine whether the session belongs to the given playtest.
     *
     * Used where a playtest has been resolved separately from the session, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToPlaytest(Playtest $playtest): bool
    {
        return $this->playtest_id === $playtest->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlaytestSessionFactory
    {
        return PlaytestSessionFactory::new();
    }
}
