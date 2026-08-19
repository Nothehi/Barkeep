<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\ResourceTypeFactory;

/**
 * One thing players hold, gain and spend.
 *
 * Wood, gold, action points, victory points, cards in hand, health. Nothing here
 * is hardcoded and nothing is seeded: a designer declares the resources their
 * game has, because a platform that shipped with a list of board-game resources
 * would be telling every studio what kind of game they are making.
 *
 * What separates gold from action points is the four flags rather than the
 * category. Gold accumulates, is tradeable and is spent; action points are spent
 * and nothing else. The category is classification for grouping a list, and it
 * deliberately controls no behaviour — see {@see ResourceCategory} for why that
 * separation is load-bearing.
 *
 * The bounds are all nullable, and null means unbounded rather than zero. A
 * resource with no ceiling is a shape the analysis reports on; a resource capped
 * at zero is nonsense, and a schema that could not tell them apart would produce
 * one or the other by accident.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $name
 * @property string $slug
 * @property ResourceCategory $category
 * @property string|null $description
 * @property string|null $unit
 * @property bool $is_tradeable
 * @property bool $is_accumulative
 * @property bool $is_spendable
 * @property bool $is_convertible
 * @property Quantity|null $min_value
 * @property Quantity|null $max_value
 * @property Quantity|null $starting_value
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read Collection<int, ResourceFlow> $flows
 * @property-read Collection<int, ActionCost> $costs
 * @property-read Collection<int, ActionReward> $rewards
 * @property-read int|null $flows_count
 * @property-read int|null $costs_count
 * @property-read int|null $rewards_count
 */
#[Fillable(['name', 'description', 'unit'])]
class ResourceType extends Model
{
    /** @use HasFactory<ResourceTypeFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * The flags describe the ordinary case — a material you gather, hold and
     * spend — so a designer only edits the ones that differ from it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => ResourceCategory::Other->value,
        'is_tradeable' => true,
        'is_accumulative' => true,
        'is_spendable' => true,
        'is_convertible' => false,
        'position' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ResourceCategory::class,
            'is_tradeable' => 'boolean',
            'is_accumulative' => 'boolean',
            'is_spendable' => 'boolean',
            'is_convertible' => 'boolean',
            'min_value' => AsQuantity::class,
            'max_value' => AsQuantity::class,
            'starting_value' => AsQuantity::class,
            'position' => 'integer',
        ];
    }

    /**
     * The configuration this resource is part of.
     *
     * @return BelongsTo<BalanceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(BalanceProfile::class, 'balance_profile_id');
    }

    /**
     * Every declared way this resource moves.
     *
     * @return HasMany<ResourceFlow, $this>
     */
    public function flows(): HasMany
    {
        return $this->hasMany(ResourceFlow::class);
    }

    /**
     * Every action priced in this resource.
     *
     * @return HasMany<ActionCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(ActionCost::class);
    }

    /**
     * Every action that pays this resource out.
     *
     * @return HasMany<ActionReward, $this>
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ActionReward::class);
    }

    /**
     * The resource's stable handle as a value object.
     */
    public function handle(): EconomySlug
    {
        return EconomySlug::fromString($this->slug);
    }

    /**
     * How the resource reads with its unit: "3 wood", "3 cubes".
     *
     * Falls back to the name when no unit was given, which is the common case —
     * most designers call the unit after the resource.
     */
    public function amountLabel(Quantity $amount): string
    {
        return trim($amount->label().' '.($this->unit ?? $this->name));
    }

    /**
     * Determine whether the resource declares a ceiling.
     */
    public function hasMaximum(): bool
    {
        return $this->max_value !== null;
    }

    /**
     * Determine whether a value falls inside the resource's declared bounds.
     */
    public function allows(Quantity $value): bool
    {
        return $value->isWithin($this->min_value, $this->max_value);
    }

    /**
     * Determine whether the resource belongs to the given profile.
     *
     * Asked wherever a resource and a profile have been resolved separately,
     * which is the invariant the database cannot express — nothing in the schema
     * says a cost's action and its resource share a configuration.
     */
    public function belongsToProfile(BalanceProfile $profile): bool
    {
        return $this->balance_profile_id === $profile->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ResourceTypeFactory
    {
        return ResourceTypeFactory::new();
    }
}
