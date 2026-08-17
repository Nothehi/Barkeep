<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\FrameworkFactory;
use Modules\Identity\Domain\Models\User;

/**
 * A complete board-game design methodology.
 *
 * The root of this module, and deliberately almost empty: a name, an address, a
 * sentence about what it is for, and a lifecycle. Everything a designer actually
 * follows — the phases, the principles, the criteria, the practices, the prompts,
 * the checklists — belongs to a *version* of the framework rather than to the
 * framework itself.
 *
 * That split is the module's foundation. A methodology is a living document that
 * gets better as its author learns; a game that is halfway through following one
 * needs it to hold still. Versions are how both are true at once, and the reason
 * this model owns nothing that a game reads.
 *
 * A framework has no workspace. It is not a document inside a studio — it is
 * something Barkeep publishes, which studios adopt. What connects the two is
 * {@see GameFramework}, and it points at a version.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property FrameworkStatus $status
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $creator
 * @property-read Collection<int, FrameworkVersion> $versions
 * @property-read FrameworkVersion|null $latestVersion
 * @property-read int|null $versions_count
 */
#[Fillable(['name', 'description'])]
class Framework extends Model
{
    /** @use HasFactory<FrameworkFactory> */
    use HasFactory, HasUuids;

    /**
     * The route key used in human facing URLs.
     *
     * Frameworks are addressed by slug and their slugs are globally unique, so
     * unlike a game this needs no scoping — `/app/frameworks/board-game-design`
     * means one thing everywhere.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The model's default attribute values.
     *
     * Set here as well as on the column so a freshly built framework reports its
     * state before being reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => FrameworkStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FrameworkStatus::class,
        ];
    }

    /**
     * The account that wrote the methodology down.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Every edition of the framework, oldest first.
     *
     * @return HasMany<FrameworkVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(FrameworkVersion::class)->orderBy('version_number');
    }

    /**
     * The framework's newest edition, whatever state it is in.
     *
     * Ordered by version number rather than by creation time: the number is the
     * thing the domain guarantees is unique and ordered, and it is what somebody
     * means by "the latest one".
     *
     * @return HasOne<FrameworkVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(FrameworkVersion::class)->ofMany('version_number', 'max');
    }

    /**
     * Get the framework's address as a value object.
     */
    public function slug(): FrameworkSlug
    {
        return FrameworkSlug::fromString($this->slug);
    }

    /**
     * Determine whether the framework's own record may still be changed.
     *
     * Only answers for the framework row — its name, address and description.
     * Whether a *version* may change is the version's own question, and a
     * published framework happily gains new draft versions: that is the
     * mechanism by which a methodology evolves at all.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the framework is visible to designers at large.
     */
    public function isPublished(): bool
    {
        return $this->status === FrameworkStatus::Published;
    }

    /**
     * Determine whether the framework has been retired.
     */
    public function isArchived(): bool
    {
        return $this->status === FrameworkStatus::Archived;
    }

    /**
     * Determine whether new versions may still be opened against it.
     *
     * An archived framework may not. Everything else may, including a published
     * one — see {@see isModifiable()}.
     */
    public function acceptsNewVersions(): bool
    {
        return ! $this->isArchived();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): FrameworkFactory
    {
        return FrameworkFactory::new();
    }
}
