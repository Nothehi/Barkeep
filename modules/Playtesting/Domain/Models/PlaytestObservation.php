<?php

namespace Modules\Playtesting\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories\PlaytestObservationFactory;

/**
 * Something a designer noticed during a session.
 *
 * The raw material of playtesting. "Player misunderstood the scoring rule",
 * "all three players ignored the market until round four", "the game stalled
 * after round five" — small, specific, written while it was still happening.
 *
 * Not to be confused with {@see PlaytestFeedback}. An observation is what
 * somebody watching saw; feedback is what a player said. Keeping them apart is
 * what stops a designer's interpretation from being read back later as a
 * player's own words.
 *
 * @property string $id
 * @property string $session_id
 * @property string|null $participant_id
 * @property ObservationCategory $category
 * @property string $content
 * @property CarbonImmutable|null $observed_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PlaytestSession|null $session
 * @property-read PlaytestParticipant|null $participant
 * @property-read User|null $creator
 */
#[Fillable(['content'])]
class PlaytestObservation extends Model
{
    /** @use HasFactory<PlaytestObservationFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => ObservationCategory::Other->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ObservationCategory::class,
            'observed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The session this was noticed at.
     *
     * @return BelongsTo<PlaytestSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PlaytestSession::class, 'session_id');
    }

    /**
     * The person it was about, when it was about one person.
     *
     * @return BelongsTo<PlaytestParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(PlaytestParticipant::class, 'participant_id');
    }

    /**
     * The account that recorded it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * When this belongs on the session's timeline.
     *
     * Prefers the moment it was observed and falls back to when it was
     * written down, so an observation typed up after the session still has
     * somewhere to sit rather than dropping out of the account entirely.
     */
    public function occurredAt(): ?CarbonImmutable
    {
        return $this->observed_at ?? $this->created_at;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlaytestObservationFactory
    {
        return PlaytestObservationFactory::new();
    }
}
