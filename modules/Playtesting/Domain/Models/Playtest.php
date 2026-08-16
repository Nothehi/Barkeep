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
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories\PlaytestFactory;

/**
 * A deliberate attempt to find something out about a version of a game.
 *
 * The aggregate this module is built around, and the thing that makes
 * playtesting more than a diary. A playtest names a question — the objective —
 * optionally states what the designer expects the answer to be, and then
 * gathers evidence towards it across however many sittings it takes.
 *
 * It belongs to exactly one game *and* one version of that game, and the two
 * have to agree. That invariant is the module's foundation: a playtest whose
 * version came from a different game is evidence about a design nobody played.
 *
 * Nothing about the version is copied here. GameDesign owns it, the playtest
 * points at it, and it keeps pointing at it after the design has moved on —
 * which is what lets somebody read a year-old playtest and know what was
 * actually on the table.
 *
 * @property string $id
 * @property string $game_id
 * @property string $game_version_id
 * @property string $title
 * @property string $objective
 * @property string|null $hypothesis
 * @property string|null $conclusion
 * @property PlaytestStatus $status
 * @property CarbonImmutable|null $planned_at
 * @property CarbonImmutable|null $completed_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read GameVersion|null $version
 * @property-read User|null $creator
 * @property-read Collection<int, PlaytestSession> $sessions
 * @property-read int|null $sessions_count
 */
#[Fillable(['title', 'objective', 'hypothesis', 'conclusion'])]
class Playtest extends Model
{
    /** @use HasFactory<PlaytestFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * Set here as well as on the column so a freshly built playtest reports
     * its state before being reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PlaytestStatus::Planned->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PlaytestStatus::class,
            'planned_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The game being tested.
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * The exact iteration that was on the table.
     *
     * @return BelongsTo<GameVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }

    /**
     * The account that planned the playtest.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Every sitting of this playtest.
     *
     * @return HasMany<PlaytestSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(PlaytestSession::class);
    }

    /**
     * Determine whether the playtest's plan may still be rewritten.
     *
     * Only answers for the playtest. Whether the game around it is still
     * accepting changes is a separate question, and both have to be true —
     * see the policy and the guard, which check each in turn.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether what was learned may still be written down.
     */
    public function acceptsAnalysis(): bool
    {
        return $this->status->allowsAnalysis();
    }

    /**
     * Determine whether the playtest is over, however it ended.
     */
    public function isClosed(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Determine whether the playtest belongs to the given game.
     *
     * Used where a game has been resolved separately from the playtest, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->game_id === $game->getKey();
    }

    /**
     * Determine whether the given version is the one under test.
     */
    public function testsVersion(GameVersion $version): bool
    {
        return $this->game_version_id === $version->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlaytestFactory
    {
        return PlaytestFactory::new();
    }
}
