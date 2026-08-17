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
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\FrameworkVersionFactory;
use Modules\Identity\Domain\Models\User;

/**
 * One edition of a methodology, and the thing games actually follow.
 *
 * Everything a designer reads hangs off a version rather than off the framework:
 * the phases, and through them the principles, criteria, practices, prompts and
 * checklists. That is what makes a methodology able to improve without rewriting
 * the past. When v2 splits a phase in two, renames a criterion and drops a
 * checklist, a game that adopted v1 is unaffected — it is reading different rows.
 *
 * Publishing is the moment a version stops being a draft and starts being a
 * contract. Before it, the content is a work in progress and only framework
 * administrators can see it. After it, the content is frozen and games may adopt
 * it. There is no way back: unpublishing would let the questions change
 * underneath the answers, which is the one failure versioning exists to prevent.
 * Enforcement lives in `FrameworkVersionGuard` rather than only in a policy, so a
 * caller arriving from a console command or a seeder is refused too.
 *
 * @property string $id
 * @property string $framework_id
 * @property int $version_number
 * @property string|null $name
 * @property string|null $description
 * @property FrameworkStatus $status
 * @property CarbonImmutable|null $published_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Framework|null $framework
 * @property-read User|null $creator
 * @property-read Collection<int, DesignPhaseDefinition> $phases
 * @property-read Collection<int, DesignPrinciple> $principles
 * @property-read Collection<int, DesignCriterion> $criteria
 * @property-read Collection<int, DesignPractice> $practices
 * @property-read Collection<int, DesignPrompt> $prompts
 * @property-read Collection<int, Checklist> $checklists
 * @property-read Collection<int, GameFramework> $adoptions
 * @property-read int|null $phases_count
 * @property-read int|null $adoptions_count
 */
#[Fillable(['name', 'description'])]
class FrameworkVersion extends Model
{
    /** @use HasFactory<FrameworkVersionFactory> */
    use HasFactory, HasUuids;

    /**
     * The route key used in human facing URLs.
     *
     * Versions are addressed by their number, resolved through the bound
     * framework. A number is only meaningful inside one framework, so every
     * version route is nested — the same arrangement GameDesign uses for a
     * game's iterations.
     */
    public function getRouteKeyName(): string
    {
        return 'version_number';
    }

    /**
     * The model's default attribute values.
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
            'version_number' => 'integer',
            'status' => FrameworkStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * The methodology this is an edition of.
     *
     * @return BelongsTo<Framework, $this>
     */
    public function framework(): BelongsTo
    {
        return $this->belongsTo(Framework::class);
    }

    /**
     * The account that opened the version.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The version's stages, in the order a designer works through them.
     *
     * @return HasMany<DesignPhaseDefinition, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(DesignPhaseDefinition::class, 'framework_version_id')->ordered();
    }

    /**
     * @return HasMany<DesignPrinciple, $this>
     */
    public function principles(): HasMany
    {
        return $this->hasMany(DesignPrinciple::class, 'framework_version_id')->ordered();
    }

    /**
     * @return HasMany<DesignCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(DesignCriterion::class, 'framework_version_id')->ordered();
    }

    /**
     * @return HasMany<DesignPractice, $this>
     */
    public function practices(): HasMany
    {
        return $this->hasMany(DesignPractice::class, 'framework_version_id')->ordered();
    }

    /**
     * @return HasMany<DesignPrompt, $this>
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(DesignPrompt::class, 'framework_version_id')->ordered();
    }

    /**
     * @return HasMany<Checklist, $this>
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class, 'framework_version_id')->ordered();
    }

    /**
     * The games following this edition.
     *
     * The relation a framework author needs before publishing the next version:
     * "who is on v1?" is the question that makes migration a real decision rather
     * than an afterthought.
     *
     * @return HasMany<GameFramework, $this>
     */
    public function adoptions(): HasMany
    {
        return $this->hasMany(GameFramework::class, 'framework_version_id');
    }

    /**
     * Get the version's ordinal as a value object.
     */
    public function number(): FrameworkVersionNumber
    {
        return FrameworkVersionNumber::fromInt($this->version_number);
    }

    /**
     * How the version is written wherever people read it.
     *
     * The number is the identity — "v1" — and the name, when there is one, sits
     * beside it rather than replacing it. A designer citing a framework says
     * "we're on v1", never "we're on First Public Edition".
     */
    public function label(): string
    {
        return $this->number()->label();
    }

    /**
     * Determine whether the version and everything inside it may still change.
     *
     * The module's central invariant in one method. Only answers for the version;
     * whether the framework around it has been archived is a separate question,
     * and both have to be true — see `FrameworkVersionGuard`, which checks each in
     * turn.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the version has been frozen and released.
     */
    public function isPublished(): bool
    {
        return $this->status === FrameworkStatus::Published;
    }

    /**
     * Determine whether games may adopt this version.
     */
    public function allowsAdoption(): bool
    {
        return $this->status->allowsAdoption();
    }

    /**
     * Determine whether the version belongs to the given framework.
     *
     * Used where a framework has been resolved separately from the version, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToFramework(Framework $framework): bool
    {
        return $this->framework_id === $framework->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): FrameworkVersionFactory
    {
        return FrameworkVersionFactory::new();
    }
}
