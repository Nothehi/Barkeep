<?php

namespace Modules\GameDesign\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\GameDesign\Domain\Enums\MechanicCategory;
use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\ValueObjects\MechanicSlug;
use Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories\MechanicFactory;

/**
 * One term in the platform's shared design vocabulary.
 *
 * A mechanic is a word with a definition — worker placement, set collection,
 * push your luck — and it belongs to nobody. That is the whole point. A game
 * does not write down "worker placement" as free text; it claims this row, so
 * that two studios describing the same idea are describing the same thing and
 * anything that reads across games has something to read.
 *
 * ## Why it is a table rather than an enum
 *
 * The rest of this module's fixed sets are enums — `GameStatus`, `DesignPhase`
 * — because they are decisions the product has made and a designer cannot add
 * to. A vocabulary is the opposite kind of list: it is expected to grow, the
 * people who should grow it are not the people who deploy, and being one term
 * short should not require a release.
 *
 * ## What is not here
 *
 * No games. A mechanic knows nothing about who claimed it, and the relation is
 * declared on the game's design record rather than here — the vocabulary is
 * upstream of every game and stays unaware of them, which is what lets it be
 * read and cached without dragging a studio's data along.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property MechanicCategory $category
 * @property MechanicStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'description'])]
class Mechanic extends Model
{
    /** @use HasFactory<MechanicFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => MechanicStatus::Published->value,
    ];

    /**
     * The route key used in human facing URLs.
     *
     * Addressed by slug rather than by id because the vocabulary is public
     * reading — `/app/mechanics/worker-placement` is a link somebody can
     * usefully send, and a uuid is not.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => MechanicCategory::class,
            'status' => MechanicStatus::class,
        ];
    }

    /**
     * Narrow to the terms a designer may currently pick from.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('status'), MechanicStatus::Published);
    }

    /**
     * Order the vocabulary the way it is read.
     *
     * By category and then by name. The category order is the enum's, which is
     * the order a design gets built in rather than the alphabet — so it cannot
     * be expressed in SQL without restating the enum, and is applied in PHP by
     * the repository instead.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('name'));
    }

    /**
     * Get the mechanic's address as a value object.
     */
    public function slug(): MechanicSlug
    {
        return MechanicSlug::fromString($this->slug);
    }

    /**
     * Determine whether a game may newly claim this mechanic.
     */
    public function allowsAdoption(): bool
    {
        return $this->status->allowsAdoption();
    }

    /**
     * Determine whether the term has been retired from the vocabulary.
     */
    public function isArchived(): bool
    {
        return $this->status === MechanicStatus::Archived;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): MechanicFactory
    {
        return MechanicFactory::new();
    }
}
