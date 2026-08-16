<?php

namespace Modules\Playtesting\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;
use Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories\PlaytestFeedbackFactory;

/**
 * Something a participant said about a session.
 *
 * The other half of the evidence, and the half designers are worst at
 * collecting. "I didn't understand why I couldn't buy another card", "the game
 * felt too long", "I never knew what my best move was" — a player's own
 * account of playing, in their words rather than in the designer's.
 *
 * The optional rating is a structured signal beside the words, not a survey.
 * It exists so a playtest can report an average across sessions without
 * anybody having to read every comment first; the comment is still where the
 * useful part is. Custom questionnaires are a capability the platform does not
 * have yet, and inventing half of one here would make the real thing harder to
 * build.
 *
 * @property string $id
 * @property string $session_id
 * @property string|null $participant_id
 * @property string $content
 * @property int|null $rating
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PlaytestSession|null $session
 * @property-read PlaytestParticipant|null $participant
 * @property-read User|null $creator
 */
#[Fillable(['content'])]
class PlaytestFeedback extends Model
{
    /** @use HasFactory<PlaytestFeedbackFactory> */
    use HasFactory, HasUuids;

    /**
     * The table backing the model.
     *
     * Named explicitly because "feedback" is already plural in English and
     * Eloquent would otherwise look for `playtest_feedbacks`.
     *
     * @var string
     */
    protected $table = 'playtest_feedback';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * The session this was said about.
     *
     * @return BelongsTo<PlaytestSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PlaytestSession::class, 'session_id');
    }

    /**
     * Who said it, when they were willing to be named.
     *
     * @return BelongsTo<PlaytestParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(PlaytestParticipant::class, 'participant_id');
    }

    /**
     * The account that wrote it down, which is usually not who said it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The rating as a value object, if one was given.
     */
    public function rating(): ?FeedbackRating
    {
        return $this->rating === null ? null : FeedbackRating::fromInt($this->rating);
    }

    /**
     * Determine whether this feedback came with a score.
     */
    public function isRated(): bool
    {
        return $this->rating !== null;
    }

    /**
     * Determine whether this feedback was given anonymously.
     */
    public function isAnonymous(): bool
    {
        return $this->participant_id === null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlaytestFeedbackFactory
    {
        return PlaytestFeedbackFactory::new();
    }
}
