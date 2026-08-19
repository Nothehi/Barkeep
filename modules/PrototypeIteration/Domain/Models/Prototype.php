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
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\PrototypeFactory;

/**
 * A buildable, testable implementation of a design.
 *
 * The distinction between this and a game version is the one the whole module
 * rests on, and it is easy to lose. A `GameVersion` is what the design *is* —
 * the canonical statement of the rules as they stand. A prototype is what
 * somebody can put on a table: a printed sheet of cards, a spreadsheet
 * simulation, a box of 3D printed parts. One design state can be implemented
 * several ways at once, which is why a game has prototypes rather than a
 * prototype.
 *
 * A prototype points at the game version it was built from and then outlives
 * it. That is not drift — it is the point. The design moves on, the prototype
 * gets rebuilt into a new prototype version, and each iteration records which
 * design state and which built state it was actually working with. Nothing
 * about the game or its version is copied here; GameDesign owns both.
 *
 * A prototype is archived rather than deleted once anything has been iterated
 * against it. Its versions are the things the design history points at, so
 * removing one would take a year of recorded reasoning with it.
 *
 * @property string $id
 * @property string $game_id
 * @property string $game_version_id
 * @property string $name
 * @property string|null $description
 * @property PrototypeType $type
 * @property PrototypeStatus $status
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read GameVersion|null $version
 * @property-read User|null $creator
 * @property-read Collection<int, PrototypeVersion> $versions
 * @property-read int|null $versions_count
 */
#[Fillable(['name', 'description'])]
class Prototype extends Model
{
    /** @use HasFactory<PrototypeFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * Set here as well as on the columns so a freshly built prototype reports
     * its kind and state before being reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => PrototypeType::Paper->value,
        'status' => PrototypeStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PrototypeType::class,
            'status' => PrototypeStatus::class,
        ];
    }

    /**
     * The game this is a prototype of.
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * The design state the prototype was first built from.
     *
     * Named `version` to match the convention the rest of the platform uses
     * for a game's iteration. The prototype's *own* states are
     * {@see versions()}, which is a different sequence entirely — a source of
     * confusion worth naming rather than papering over.
     *
     * @return BelongsTo<GameVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }

    /**
     * The account that started building it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Every recorded state of this prototype, v1 upwards.
     *
     * @return HasMany<PrototypeVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PrototypeVersion::class);
    }

    /**
     * Determine whether the prototype's own details may still be changed.
     *
     * Only answers for the prototype. Whether the game around it is still
     * accepting changes is a separate question, and both have to be true —
     * see the policy and the guard, which check each in turn.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the prototype may still gain versions.
     */
    public function acceptsVersions(): bool
    {
        return $this->status->allowsVersions();
    }

    /**
     * Determine whether the prototype has been put away.
     */
    public function isArchived(): bool
    {
        return $this->status === PrototypeStatus::Archived;
    }

    /**
     * Determine whether the prototype belongs to the given game.
     *
     * Used where a game has been resolved separately from the prototype, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->game_id === $game->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PrototypeFactory
    {
        return PrototypeFactory::new();
    }
}
