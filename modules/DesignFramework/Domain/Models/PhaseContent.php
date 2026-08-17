<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\Position;

/**
 * What the five kinds of framework content have in common.
 *
 * Principles, criteria, practices, prompts and checklists are five different
 * things to a designer and the same thing to the database: each belongs to a
 * framework version, is optionally filed under one of its phases, carries a
 * title and an address, holds a position among its siblings, and has a status.
 * They differ only in their body — a description, a set of instructions, a
 * question — and in what the module *does* with them.
 *
 * Sharing a base class here rather than repeating the arrangement five times is
 * a deliberate exception to the module's otherwise explicit style, and it pays
 * for itself in one specific way: the ordering rules, the phase relationship and
 * the visibility rules have a single definition. Section 17 asks for ordering
 * logic to be centralised, and the sequencer that rewrites positions is written
 * against this type — so a sixth content type gets correct ordering by
 * inheriting rather than by somebody remembering.
 *
 * What is *not* here is anything about a game. No content row ever holds an
 * evaluation, a completion or a response: those belong to a game's adoption of
 * the version, in their own tables. Keeping them out is the separation section 22
 * calls critical, and the reason this class has no notion of being "done".
 *
 * @property string $id
 * @property string $framework_version_id
 * @property string|null $phase_id
 * @property string $title
 * @property string $slug
 * @property int $position
 * @property FrameworkContentStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read FrameworkVersion|null $version
 * @property-read DesignPhaseDefinition|null $phase
 */
abstract class PhaseContent extends Model
{
    use HasUuids;

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
     * The edition of the methodology this belongs to.
     *
     * Content belongs to a version, never to a framework. That is what makes it
     * safe for v2 to say something different from v1.
     *
     * @return BelongsTo<FrameworkVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(FrameworkVersion::class, 'framework_version_id');
    }

    /**
     * The stage this is filed under, if any.
     *
     * Null means it applies across the whole methodology. "Every decision should
     * have meaningful consequences" is not advice about one phase, and forcing
     * such content into an arbitrary stage would bury it.
     *
     * @return BelongsTo<DesignPhaseDefinition, $this>
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(DesignPhaseDefinition::class, 'phase_id');
    }

    /**
     * Order content the way the domain says it is ordered.
     *
     * `position` and nothing else, with creation time only as a tiebreaker so
     * the order is total rather than left to the database's whim when two rows
     * somehow share a position. Never `id`, and never `created_at` alone — see
     * {@see Position} for why.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('created_at');
    }

    /**
     * Narrow to the content designers following the framework should see.
     *
     * The column is qualified because this scope is applied over a join to
     * `design_phases` — which has a `status` of its own — whenever content is read
     * in phase order. An unqualified `status` is ambiguous there, and the failure
     * is a database error on the busiest read in the module rather than a wrong
     * answer, so qualifying here is what keeps the scope safe wherever it is used.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('status'), FrameworkContentStatus::Published);
    }

    /**
     * Narrow to the content filed under a particular phase, or under none.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInPhase(Builder $query, ?DesignPhaseDefinition $phase): Builder
    {
        return $phase === null
            ? $query->whereNull('phase_id')
            : $query->where('phase_id', $phase->getKey());
    }

    /**
     * Get the content's address as a value object.
     */
    public function slug(): ContentSlug
    {
        return ContentSlug::fromString($this->slug);
    }

    /**
     * Get the content's place among its siblings as a value object.
     */
    public function position(): Position
    {
        return Position::fromInt($this->position);
    }

    /**
     * Determine whether designers following the framework should see this.
     */
    public function isVisibleToDesigners(): bool
    {
        return $this->status->isVisibleToDesigners();
    }

    /**
     * Determine whether this content counts towards a game's progress.
     */
    public function countsTowardsProgress(): bool
    {
        return $this->status->countsTowardsProgress();
    }

    /**
     * Determine whether the content belongs to the given version.
     *
     * Used where a version has been resolved separately from the content — from a
     * game's adoption, for instance — so that the two are proved to match rather
     * than assumed to.
     */
    public function belongsToVersion(FrameworkVersion $version): bool
    {
        return $this->framework_version_id === $version->getKey();
    }
}
