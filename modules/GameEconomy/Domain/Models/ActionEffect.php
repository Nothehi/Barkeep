<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\ActionEffectFactory;

/**
 * Something an action does that is not a quantity of a resource.
 *
 * "Unlock Building II." "Increase maximum hand size by 2." "Close the northern
 * route."
 *
 * The target is free text and there is no foreign key to anything, which is the
 * whole reason this table exists rather than being folded into costs and
 * rewards. The things an effect acts on are not all rows — "building level 2" is
 * not a resource — and modelling one as a resource to earn a foreign key would
 * be the schema telling the designer what their game is allowed to contain.
 *
 * The analysis counts effects and never values them. An action that unlocks a
 * technology and pays nothing is a real action, and the count is what stops the
 * module from calling half a technology tree pointless.
 *
 * @property string $id
 * @property string $action_id
 * @property ActionEffectType $effect_type
 * @property string $target
 * @property Quantity|null $value
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read EconomyAction|null $action
 */
#[Fillable(['target', 'description'])]
class ActionEffect extends Model
{
    /** @use HasFactory<ActionEffectFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'effect_type' => ActionEffectType::Other->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effect_type' => ActionEffectType::class,
            'value' => AsQuantity::class,
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
     * Determine whether this effect states a magnitude where one was expected.
     *
     * An unlock needs none; a capacity modifier without one says nothing at all.
     */
    public function isQuantified(): bool
    {
        return ! $this->effect_type->expectsValue() || $this->value !== null;
    }

    /**
     * How the effect reads in a list: "Maximum hand size +2", "Unlock Building II".
     */
    public function label(): string
    {
        if ($this->value === null) {
            return $this->target;
        }

        $amount = $this->value;

        return $this->target.' '.($amount->isNegative() ? $amount->label() : '+'.$amount->label());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ActionEffectFactory
    {
        return ActionEffectFactory::new();
    }
}
