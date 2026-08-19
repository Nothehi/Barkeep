<?php

namespace Modules\PrototypeIteration\Domain\Models;

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
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\IterationFactory;

/**
 * One turn of the design loop, and the aggregate this module exists for.
 *
 * An iteration is the unit of actual design work: somebody said what they
 * wanted to change and why, changed it, tested it, looked at what happened, and
 * decided what to do next. Everything hanging off it — the changes, the
 * experiments, the attached playtests, the decisions, the outcome — is one
 * stage of that sentence, and keeping them as separate records rather than as
 * fields on a "design note" is what lets the history be read back stage by
 * stage years later.
 *
 * It names three things and all three have to agree:
 *
 * - the game, which is the project;
 * - the game version, which is the design as it stood;
 * - the prototype version, which is the built thing that was on the table.
 *
 * The third is where the invariant bites. A prototype version belonging to
 * another game would produce an iteration that reads perfectly and describes
 * work nobody did — so the pair is proved through the game's own prototypes
 * before anything is written, and there is a test that attempts exactly that
 * forgery.
 *
 * A completed iteration is a historical record. Its plan, its changes and its
 * decisions stop being editable, because the next cycle is built on what this
 * one concluded and a history that can be quietly revised is not one anybody
 * can reason from. A later change of mind is a new iteration — which is also
 * how a designer would describe it.
 *
 * @property string $id
 * @property string $game_id
 * @property string $game_version_id
 * @property string $prototype_version_id
 * @property string $title
 * @property string $objective
 * @property string|null $hypothesis
 * @property IterationStatus $status
 * @property IterationOutcome|null $outcome
 * @property string|null $summary
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read GameVersion|null $version
 * @property-read PrototypeVersion|null $prototypeVersion
 * @property-read User|null $creator
 * @property-read Collection<int, DesignChange> $changes
 * @property-read Collection<int, DesignExperiment> $experiments
 * @property-read Collection<int, DesignDecision> $decisions
 * @property-read Collection<int, IterationPlaytest> $playtestLinks
 * @property-read int|null $changes_count
 * @property-read int|null $experiments_count
 * @property-read int|null $decisions_count
 * @property-read int|null $playtest_links_count
 */
#[Fillable(['title', 'objective', 'hypothesis'])]
class Iteration extends Model
{
    /** @use HasFactory<IterationFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => IterationStatus::Planned->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IterationStatus::class,
            'outcome' => IterationOutcome::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The project this cycle belongs to.
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * The design as it stood when the cycle ran.
     *
     * @return BelongsTo<GameVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }

    /**
     * The built thing that was on the table.
     *
     * @return BelongsTo<PrototypeVersion, $this>
     */
    public function prototypeVersion(): BelongsTo
    {
        return $this->belongsTo(PrototypeVersion::class, 'prototype_version_id');
    }

    /**
     * The account that planned the cycle.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * What was deliberately changed.
     *
     * @return HasMany<DesignChange, $this>
     */
    public function changes(): HasMany
    {
        return $this->hasMany(DesignChange::class, 'iteration_id');
    }

    /**
     * What was deliberately tried out.
     *
     * @return HasMany<DesignExperiment, $this>
     */
    public function experiments(): HasMany
    {
        return $this->hasMany(DesignExperiment::class, 'iteration_id');
    }

    /**
     * What was concluded.
     *
     * @return HasMany<DesignDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(DesignDecision::class, 'iteration_id');
    }

    /**
     * The playtests this cycle was tested through.
     *
     * Join rows rather than playtests. Playtesting owns the playtest, and
     * everything shown about one is read back through its own contract — see
     * `PlaytestEvidence`, which is the only file in this module that knows
     * Playtesting exists.
     *
     * @return HasMany<IterationPlaytest, $this>
     */
    public function playtestLinks(): HasMany
    {
        return $this->hasMany(IterationPlaytest::class, 'iteration_id');
    }

    /**
     * Determine whether the iteration's plan may still be rewritten.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether design work may still be recorded against it.
     *
     * The same window as the plan being editable, which is deliberate: a change
     * or a decision recorded against a finished cycle is one nobody can date.
     */
    public function acceptsWork(): bool
    {
        return $this->status->allowsWork();
    }

    /**
     * Determine whether the cycle is under way right now.
     */
    public function isRunning(): bool
    {
        return $this->status === IterationStatus::InProgress;
    }

    /**
     * Determine whether the cycle is over, however it ended.
     */
    public function isClosed(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Determine whether the iteration reached a recorded outcome.
     */
    public function hasOutcome(): bool
    {
        return $this->outcome !== null;
    }

    /**
     * Determine whether the iteration belongs to the given game.
     *
     * Used where a game has been resolved separately from the iteration, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->game_id === $game->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): IterationFactory
    {
        return IterationFactory::new();
    }
}
