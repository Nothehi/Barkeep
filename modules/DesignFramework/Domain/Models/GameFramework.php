<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\GameFrameworkFactory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * A game's adoption of one edition of one methodology.
 *
 * The join that lets this module be about methodology without owning any games.
 * A game points at a framework version; everything the studio records while
 * working through it — evaluations, completions, responses — hangs off *this*
 * row rather than off the game or off the framework content.
 *
 * ## Why the relationship is historical
 *
 * The version is captured at adoption and never changes. When v2 is published, a
 * game on v1 stays on v1: it keeps reading v1's phases, and its evaluations keep
 * pointing at the criteria those phases actually asked. Silently moving it would
 * leave answers attached to questions that had been reworded, which is the exact
 * failure versioning exists to prevent. The database enforces the other half — a
 * version that games are following cannot be deleted.
 *
 * Migration is therefore a real operation with real decisions in it: which
 * evaluations carry over, what happens to a criterion that no longer exists. The
 * module does not implement it, and does not pretend to by allowing reassignment.
 *
 * ## What is not here
 *
 * No game fields, and no version fields. Nothing about the game is copied — its
 * name, its status and its workspace are GameDesign's business and are read
 * through the relation. Nothing about the version is copied either, which is what
 * keeps "the game follows v1" a fact with one home.
 *
 * @property string $id
 * @property string $game_id
 * @property string $framework_version_id
 * @property GameFrameworkStatus $status
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $completed_at
 * @property string $adopted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read FrameworkVersion|null $version
 * @property-read User|null $adopter
 * @property-read Collection<int, CriterionEvaluation> $criterionEvaluations
 * @property-read Collection<int, PracticeCompletion> $practiceCompletions
 * @property-read Collection<int, ChecklistItemCompletion> $checklistItemCompletions
 * @property-read Collection<int, PromptResponse> $promptResponses
 */
class GameFramework extends Model
{
    /** @use HasFactory<GameFrameworkFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => GameFrameworkStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameFrameworkStatus::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The design project following the methodology.
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * The exact edition the game is following, forever.
     *
     * @return BelongsTo<FrameworkVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FrameworkVersion::class, 'framework_version_id');
    }

    /**
     * The account that took the methodology up.
     *
     * @return BelongsTo<User, $this>
     */
    public function adopter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adopted_by');
    }

    /**
     * This game's answers to the version's criteria.
     *
     * @return HasMany<CriterionEvaluation, $this>
     */
    public function criterionEvaluations(): HasMany
    {
        return $this->hasMany(CriterionEvaluation::class);
    }

    /**
     * The version's practices this game has carried out.
     *
     * @return HasMany<PracticeCompletion, $this>
     */
    public function practiceCompletions(): HasMany
    {
        return $this->hasMany(PracticeCompletion::class);
    }

    /**
     * The checklist requirements this game has met.
     *
     * @return HasMany<ChecklistItemCompletion, $this>
     */
    public function checklistItemCompletions(): HasMany
    {
        return $this->hasMany(ChecklistItemCompletion::class);
    }

    /**
     * This game's answers to the version's prompts.
     *
     * @return HasMany<PromptResponse, $this>
     */
    public function promptResponses(): HasMany
    {
        return $this->hasMany(PromptResponse::class);
    }

    /**
     * Determine whether the game may still record work against the framework.
     *
     * Only answers for the adoption. Whether the game around it is still
     * accepting changes is a separate question, and both have to be true — see the
     * policy and `GameFrameworkGuard`, which check each in turn.
     */
    public function acceptsProgress(): bool
    {
        return $this->status->allowsProgress();
    }

    /**
     * Determine whether the studio has declared itself finished.
     */
    public function isComplete(): bool
    {
        return $this->status === GameFrameworkStatus::Completed;
    }

    /**
     * Determine whether the adoption belongs to the given game.
     *
     * Used where a game has been resolved separately from the adoption, so that
     * the two are proved to match rather than assumed to.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->game_id === $game->getKey();
    }

    /**
     * Determine whether the adoption is of the given version.
     */
    public function follows(FrameworkVersion $version): bool
    {
        return $this->framework_version_id === $version->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GameFrameworkFactory
    {
        return GameFrameworkFactory::new();
    }
}
