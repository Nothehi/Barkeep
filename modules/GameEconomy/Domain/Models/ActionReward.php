<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\ActionRewardFactory;

/**
 * One resource an action pays out, and how much of it.
 *
 * The mirror of {@see ActionCost}, and a separate table rather than the same one
 * with a direction column. Costs and rewards are asked about separately
 * everywhere — profitability subtracts one from the other, the warnings read one
 * at a time, the editor puts them in two panels — so a shared table would mean
 * every query in the module carrying a filter it could forget.
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
class ActionReward extends Model
{
    /** @use HasFactory<ActionRewardFactory> */
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
     * The figure the analysis counts this reward as.
     */
    public function effectiveAmount(): Quantity
    {
        return $this->amount;
    }

    /**
     * Determine whether the reward swings and the range says how far.
     */
    public function hasRange(): bool
    {
        return $this->is_variable && ($this->min_amount !== null || $this->max_amount !== null);
    }

    /**
     * Determine whether the reward is marked as swinging but never says how far.
     */
    public function isUnboundedVariable(): bool
    {
        return $this->is_variable && ! $this->hasRange();
    }

    /**
     * The largest amount this reward can pay.
     *
     * The upper bound where there is one, and the stated amount otherwise. What
     * the analysis compares against a resource's ceiling, because a reward that
     * *can* overflow is worth pointing at even when it usually does not.
     */
    public function highestAmount(): Quantity
    {
        return $this->max_amount ?? $this->amount;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ActionRewardFactory
    {
        return ActionRewardFactory::new();
    }
}
