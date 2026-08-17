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
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\Position;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\ChecklistItemFactory;

/**
 * One requirement on a checklist.
 *
 * "Core action identified." "Win condition implemented." "Loss condition
 * implemented."
 *
 * Binary, and it stays binary — see {@see ChecklistItemState}. There is no
 * in-progress, no blocked, no not-applicable. Whether a particular game has met
 * the requirement is the existence of a {@see ChecklistItemCompletion} against
 * that game's adoption, so there is exactly one representation of the fact and no
 * flag that can disagree with it.
 *
 * The one nuance is {@see $required}. An optional item is shown and can be
 * ticked, but does not count towards progress, which is what lets a framework
 * author add a suggestion without making every game following the version look
 * less finished than it was yesterday.
 *
 * Items belong to a checklist rather than to a version, which is why this is not
 * a {@see PhaseContent}: it has no phase of its own and its address is unique
 * within its list rather than within the version.
 *
 * @property string $id
 * @property string $checklist_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $satisfied_by
 * @property int $position
 * @property bool $required
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Checklist|null $checklist
 * @property-read Collection<int, ChecklistItemCompletion> $completions
 */
#[Fillable(['title', 'description', 'required', 'satisfied_by'])]
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * Required by default, because a checklist of optional items is a list of
     * suggestions.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'required' => true,
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
            'required' => 'boolean',
        ];
    }

    /**
     * The list this requirement is part of.
     *
     * @return BelongsTo<Checklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Every game that has met this requirement.
     *
     * @return HasMany<ChecklistItemCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(ChecklistItemCompletion::class, 'checklist_item_id');
    }

    /**
     * Order items the way the domain says they are ordered.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('created_at');
    }

    /**
     * Get the item's address as a value object.
     */
    public function slug(): ContentSlug
    {
        return ContentSlug::fromString($this->slug);
    }

    /**
     * Get the item's place on the list as a value object.
     */
    public function position(): Position
    {
        return Position::fromInt($this->position);
    }

    /**
     * Determine whether the item counts towards a game's progress.
     */
    public function countsTowardsProgress(): bool
    {
        return $this->required;
    }

    /**
     * Determine whether this requirement is met by a fact rather than by a tick.
     *
     * "Player count decided" used to be a box a designer checked on their own
     * word while the platform had no idea whether it was true. An item naming a
     * fact is answered by the game's design record instead, and cannot be ticked
     * by hand at all — the way to meet it is to go and decide the thing.
     */
    public function isAnsweredByTheDesignRecord(): bool
    {
        return $this->satisfied_by !== null;
    }

    /**
     * Determine whether the item belongs to the given checklist.
     */
    public function belongsToChecklist(Checklist $checklist): bool
    {
        return $this->checklist_id === $checklist->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ChecklistItemFactory
    {
        return ChecklistItemFactory::new();
    }
}
