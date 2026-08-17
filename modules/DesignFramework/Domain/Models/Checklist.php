<?php

namespace Modules\DesignFramework\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\ChecklistFactory;

/**
 * A readiness gate: a group of requirements that have to be met together.
 *
 * "Core loop checklist." "Prototype readiness." "Playtest readiness."
 * "Production readiness."
 *
 * A checklist is the only content type with children of its own, and the only one
 * whose meaning comes from being complete rather than from any single part. "Two
 * of four" is the useful reading; which two is a detail.
 *
 * The list is content and belongs to the framework version; whether a particular
 * game has ticked its items lives in `checklist_item_completions`, against that
 * game's adoption. Only the items marked required count towards progress, which
 * is what lets an author add a nice-to-have without moving everybody's numbers.
 *
 * @property string|null $description
 * @property-read Collection<int, ChecklistItem> $items
 * @property-read int|null $items_count
 */
#[Fillable(['title', 'description'])]
class Checklist extends PhaseContent
{
    /** @use HasFactory<ChecklistFactory> */
    use HasFactory;

    /**
     * The requirements on the list, in order.
     *
     * @return HasMany<ChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->ordered();
    }

    /**
     * The requirements that have to be met for the list to count as satisfied.
     *
     * @return HasMany<ChecklistItem, $this>
     */
    public function requiredItems(): HasMany
    {
        return $this->items()->where('required', true);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ChecklistFactory
    {
        return ChecklistFactory::new();
    }
}
