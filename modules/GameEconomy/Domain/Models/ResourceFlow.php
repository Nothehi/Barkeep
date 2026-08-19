<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\ResourceFlowFactory;

/**
 * One declared way a resource moves.
 *
 * "Each player harvests 3 wood per round." "Every unfed worker costs 1 food at
 * the end of the round."
 *
 * The amount is always a positive magnitude and the direction comes from the
 * flow type, which is what keeps the net calculation unambiguous: a stored
 * "-2 generation" would be a valid contradiction, and the sum would have to
 * guess which half the designer meant.
 *
 * The condition is prose, deliberately. This module is a model and an analysis
 * layer rather than a rules engine — an expression language here would be a
 * simulator wearing a text column, and the brief is explicit that simulation is
 * a different bounded context if it ever arrives.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $resource_type_id
 * @property string $name
 * @property string|null $description
 * @property ResourceFlowType $flow_type
 * @property Quantity $amount
 * @property string|null $condition
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read ResourceType|null $resource
 */
#[Fillable(['name', 'description', 'condition'])]
class ResourceFlow extends Model
{
    /** @use HasFactory<ResourceFlowFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'flow_type' => ResourceFlowType::Generation->value,
        'amount' => 0,
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
            'flow_type' => ResourceFlowType::class,
            'amount' => AsQuantity::class,
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BalanceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(BalanceProfile::class, 'balance_profile_id');
    }

    /**
     * @return BelongsTo<ResourceType, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    /**
     * How much this flow adds to the amount of the resource in play.
     *
     * Positive for a source, negative for a sink, zero for a movement that only
     * changes who holds it. The one place the flow type's direction is applied
     * to a magnitude, so a flow type added later is counted correctly everywhere
     * by answering `direction()` and nothing else.
     */
    public function signedAmount(): Quantity
    {
        return match ($this->flow_type->direction()) {
            1 => $this->amount,
            -1 => $this->amount->negated(),
            default => Quantity::zero(),
        };
    }

    /**
     * Determine whether this flow puts the resource into the game.
     */
    public function generates(): bool
    {
        return $this->flow_type->increases();
    }

    /**
     * Determine whether this flow takes the resource out of the game.
     */
    public function consumes(): bool
    {
        return $this->flow_type->decreases();
    }

    /**
     * Determine whether the flow belongs to the given profile.
     */
    public function belongsToProfile(BalanceProfile $profile): bool
    {
        return $this->balance_profile_id === $profile->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ResourceFlowFactory
    {
        return ResourceFlowFactory::new();
    }
}
