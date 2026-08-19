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
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\ValueObjects\PrototypeVersionNumber;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\PrototypeVersionFactory;

/**
 * One concrete state of a prototype: the cards as they were printed that week.
 *
 * This is the thing an iteration is actually run against, and the reason it
 * exists separately from the prototype is that "the combat prototype" is not
 * something anybody played — v3 of it is. A designer saying "that broke in v3"
 * is naming one of these.
 *
 * Once anything has been run against a version it becomes effectively
 * immutable, which is enforced by the guard rather than by this class: an
 * iteration, a playtest or an experiment pointing at a version has recorded
 * what that version *was*, and editing it afterwards changes what the record
 * says was on the table. The next change gets the next version, which costs
 * nothing and is how designers already think.
 *
 * Its artifacts are the files that make it buildable again — print sheets,
 * card layouts, an exported build. Those hang off the version rather than off
 * the prototype for the same reason: a print sheet is only meaningful as the
 * sheet for a particular state.
 *
 * @property string $id
 * @property string $prototype_id
 * @property int $version_number
 * @property string|null $name
 * @property string|null $description
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Prototype|null $prototype
 * @property-read User|null $creator
 * @property-read Collection<int, PrototypeArtifact> $artifacts
 * @property-read Collection<int, Iteration> $iterations
 * @property-read int|null $artifacts_count
 * @property-read int|null $iterations_count
 */
#[Fillable(['name', 'description'])]
class PrototypeVersion extends Model
{
    /** @use HasFactory<PrototypeVersionFactory> */
    use HasFactory, HasUuids;

    /**
     * The route key used in human facing URLs.
     *
     * Addressed by its number, resolved through the bound prototype — the same
     * arrangement GameDesign uses for a game's versions. A number is only
     * meaningful inside one prototype, so every version route is nested and
     * scoped, and `/prototypes/core-combat/versions/3` reads the way a designer
     * would say it.
     */
    public function getRouteKeyName(): string
    {
        return 'version_number';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
        ];
    }

    /**
     * The prototype this is a state of.
     *
     * @return BelongsTo<Prototype, $this>
     */
    public function prototype(): BelongsTo
    {
        return $this->belongsTo(Prototype::class);
    }

    /**
     * The account that cut the version.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The files that make this state buildable again.
     *
     * @return HasMany<PrototypeArtifact, $this>
     */
    public function artifacts(): HasMany
    {
        return $this->hasMany(PrototypeArtifact::class, 'prototype_version_id');
    }

    /**
     * Every design cycle run against this state.
     *
     * @return HasMany<Iteration, $this>
     */
    public function iterations(): HasMany
    {
        return $this->hasMany(Iteration::class, 'prototype_version_id');
    }

    /**
     * Get the version's ordinal as a value object.
     */
    public function number(): PrototypeVersionNumber
    {
        return PrototypeVersionNumber::fromInt($this->version_number);
    }

    /**
     * How the version is written wherever people read it: v1, v2, v3.
     */
    public function label(): string
    {
        return $this->number()->label();
    }

    /**
     * Determine whether the version belongs to the given prototype.
     */
    public function belongsToPrototype(Prototype $prototype): bool
    {
        return $this->prototype_id === $prototype->getKey();
    }

    /**
     * Determine whether this version's prototype belongs to the given game.
     *
     * The check behind the module's central invariant, asked from the version's
     * side. An iteration names a game and a prototype version, and the two
     * arrive from different places, so they have to be proved to agree — see
     * `PrototypeCatalogue`, which resolves the version *through* the game and
     * makes a mismatched pair fail to resolve rather than be rejected.
     *
     * Relies on the prototype relation, so callers that have not loaded it get
     * a lazy read rather than a wrong answer.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->prototype?->belongsToGame($game) === true;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PrototypeVersionFactory
    {
        return PrototypeVersionFactory::new();
    }
}
