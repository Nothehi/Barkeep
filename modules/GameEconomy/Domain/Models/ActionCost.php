<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\ActionCostFactory;

/**
 * One resource an action takes, and how much of it.
 *
 * A normalised row rather than a key in a JSON blob, which is the difference
 * between an economy that can be analysed and one that can only be displayed.
 * "Which actions spend wood?" and "what is wood's total consumption?" are the
 * two questions this module exists to answer, and both are a `where` here.
 *
 * A variable cost — "3 to 5 wood, depending on the terrain" — carries its bounds
 * beside the amount rather than instead of it. The flag is what tells the
 * analysis to read them, so a fixed cost of 5 and a variable cost averaging 5
 * are never confused.
 *
 * @property string $id
 * @property string $action_id
 * @property string $resource_type_id
 * @property Quantity $amount
 * @property bool $is_variable
 * @property Quantity|null $min_amount
 * @property Quantity|null $max_amount
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read EconomyAction|null $action
 * @property-read ResourceType|null $resource
 */
class ActionCost extends Model
{
    /** @use HasFactory<ActionCostFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'amount' => 0,
        'is_variable' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => AsQuantity::class,
            'is_variable' => 'boolean',
            'min_amount' => AsQuantity::class,
            'max_amount' => AsQuantity::class,
        ];
    }

    /**
     * @return BelongsTo<EconomyAction, $this>
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(EconomyAction::class, 'action_id');
    }

    /**
     * @return BelongsTo<ResourceType, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    /**
     * The figure the analysis counts this cost as.
     *
     * The stated amount, always — even for a variable cost with a range around
     * it. Averaging the bounds instead would replace what the designer wrote
     * with the module's guess, and a designer who set 3–7 with a typical value
     * of 4 means 4.
     */
    public function effectiveAmount(): Quantity
    {
        return $this->amount;
    }

    /**
     * Determine whether the cost swings and the range says how far.
     */
    public function hasRange(): bool
    {
        return $this->is_variable && ($this->min_amount !== null || $this->max_amount !== null);
    }

    /**
     * Determine whether the cost is marked as swinging but never says how far.
     *
     * The shape the analysis reports: "variable" with no bounds tells a reader
     * less than a plain number would.
     */
    public function isUnboundedVariable(): bool
    {
        return $this->is_variable && ! $this->hasRange();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ActionCostFactory
    {
        return ActionCostFactory::new();
    }
}
