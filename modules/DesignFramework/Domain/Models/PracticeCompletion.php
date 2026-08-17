<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\PracticeCompletionFactory;
use Modules\Identity\Domain\Models\User;

/**
 * A record that one game carried out one of its framework's activities.
 *
 * The practice belongs to the framework; the completion belongs to the game's
 * adoption of it. Section 23 calls that separation critical, and the reason is
 * concrete: "run a two-player playtest" is permanent advice to everybody
 * following the version and a finished task for exactly one project.
 *
 * The row's existence *is* the completion — there is no `completed` boolean to
 * disagree with it. Un-ticking a practice deletes the row, which keeps the state
 * genuinely binary and means a completion record always records a completion.
 *
 * The notes are the part worth having. "We ran it with four people and the market
 * never emptied" is the sentence somebody rereads while trying to remember why
 * they changed the resource costs.
 *
 * @property string $id
 * @property string $game_framework_id
 * @property string $practice_id
 * @property string $completed_by
 * @property CarbonImmutable $completed_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameFramework|null $gameFramework
 * @property-read DesignPractice|null $practice
 * @property-read User|null $completer
 */
#[Fillable(['notes'])]
class PracticeCompletion extends Model
{
    /** @use HasFactory<PracticeCompletionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The adoption this completion belongs to.
     *
     * @return BelongsTo<GameFramework, $this>
     */
    public function gameFramework(): BelongsTo
    {
        return $this->belongsTo(GameFramework::class);
    }

    /**
     * The activity that was carried out.
     *
     * @return BelongsTo<DesignPractice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(DesignPractice::class, 'practice_id');
    }

    /**
     * The account that did it, or at least recorded doing it.
     *
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PracticeCompletionFactory
    {
        return PracticeCompletionFactory::new();
    }
}
