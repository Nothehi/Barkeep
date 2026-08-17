<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\Position;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\DesignPhaseDefinitionFactory;

/**
 * One stage of a methodology, and what a designer should be working on there.
 *
 * The name is long on purpose. GameDesign already has a `DesignPhase`, and the
 * two are different concepts that would be disastrous to conflate:
 *
 * - GameDesign's `DesignPhase` is an enum on a game. It answers "where is *this
 *   game* right now?", it is chosen by the designer, and it is a fixed list the
 *   platform ships.
 * - This is a row in a framework version. It answers "what should a designer be
 *   doing at this stage?", it is authored as part of a methodology, and every
 *   framework version may define a different set.
 *
 * A game in GameDesign's `prototyping` phase might be working through this
 * module's "Core loop" phase, because the two vocabularies belong to different
 * things. Nothing in this module reads GameDesign's enum, and an architecture
 * test holds that line.
 *
 * Phases are ordered by {@see $position} and by nothing else. A framework author
 * who inserts "Concept" between "Ideation" and "Core loop" a week after writing
 * both expects it to land in the middle, which an id or a timestamp would not
 * give them.
 *
 * @property string $id
 * @property string $framework_version_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $position
 * @property FrameworkContentStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FrameworkVersion|null $version
 * @property-read Collection<int, DesignPrinciple> $principles
 * @property-read Collection<int, DesignCriterion> $criteria
 * @property-read Collection<int, DesignPractice> $practices
 * @property-read Collection<int, DesignPrompt> $prompts
 * @property-read Collection<int, Checklist> $checklists
 */
#[Fillable(['name', 'description'])]
class DesignPhaseDefinition extends Model
{
    /** @use HasFactory<DesignPhaseDefinitionFactory> */
    use HasFactory, HasUuids;

    /**
     * The table backing the model.
     *
     * Named for the concept rather than for the class, because `design_phases` is
     * what the schema calls a phase and the extra word in the class name exists
     * only to keep it apart from GameDesign's enum in PHP.
     *
     * @var string
     */
    protected $table = 'design_phases';

    /**
     * The route key used in human facing URLs.
     *
     * Phases are addressed by slug within their version, which is what makes
     * `/versions/1/phases/core-loop` readable and shareable.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => FrameworkContentStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'status' => FrameworkContentStatus::class,
        ];
    }

    /**
     * The edition of the methodology this stage belongs to.
     *
     * @return BelongsTo<FrameworkVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FrameworkVersion::class, 'framework_version_id');
    }

    /**
     * @return HasMany<DesignPrinciple, $this>
     */
    public function principles(): HasMany
    {
        return $this->hasMany(DesignPrinciple::class, 'phase_id')->ordered();
    }

    /**
     * @return HasMany<DesignCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(DesignCriterion::class, 'phase_id')->ordered();
    }

    /**
     * @return HasMany<DesignPractice, $this>
     */
    public function practices(): HasMany
    {
        return $this->hasMany(DesignPractice::class, 'phase_id')->ordered();
    }

    /**
     * @return HasMany<DesignPrompt, $this>
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(DesignPrompt::class, 'phase_id')->ordered();
    }

    /**
     * @return HasMany<Checklist, $this>
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class, 'phase_id')->ordered();
    }

    /**
     * Order phases the way the domain says they are ordered.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('created_at');
    }

    /**
     * Narrow to the phases designers following the framework should see.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', FrameworkContentStatus::Published);
    }

    /**
     * Get the phase's address as a value object.
     */
    public function slug(): ContentSlug
    {
        return ContentSlug::fromString($this->slug);
    }

    /**
     * Get the phase's place in the arc as a value object.
     */
    public function position(): Position
    {
        return Position::fromInt($this->position);
    }

    /**
     * Determine whether designers following the framework should see this phase.
     */
    public function isVisibleToDesigners(): bool
    {
        return $this->status->isVisibleToDesigners();
    }

    /**
     * Determine whether this phase counts towards a game's progress.
     */
    public function countsTowardsProgress(): bool
    {
        return $this->status->countsTowardsProgress();
    }

    /**
     * Determine whether the phase belongs to the given version.
     */
    public function belongsToVersion(FrameworkVersion $version): bool
    {
        return $this->framework_version_id === $version->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignPhaseDefinitionFactory
    {
        return DesignPhaseDefinitionFactory::new();
    }
}
