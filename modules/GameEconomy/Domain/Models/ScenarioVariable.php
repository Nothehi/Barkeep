<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\ScenarioVariableFactory;

/**
 * One value a scenario states differently: "in Rich Economy, starting gold is 15".
 *
 * The existence of this table is the guarantee. A scenario cannot modify a base
 * variable because a scenario's values are not stored on one — the base and the
 * override are different rows in different tables, combined on read and never on
 * write. That is a stronger promise than a rule in a command, because there is
 * no command that could break it.
 *
 * @property string $id
 * @property string $scenario_id
 * @property string $balance_variable_id
 * @property Quantity $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceScenario|null $scenario
 * @property-read BalanceVariable|null $variable
 */
class ScenarioVariable extends Model
{
    /** @use HasFactory<ScenarioVariableFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => AsQuantity::class,
        ];
    }

    /**
     * @return BelongsTo<BalanceScenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(BalanceScenario::class, 'scenario_id');
    }

    /**
     * @return BelongsTo<BalanceVariable, $this>
     */
    public function variable(): BelongsTo
    {
        return $this->belongsTo(BalanceVariable::class, 'balance_variable_id');
    }

    /**
     * How far this override moves the number from its base.
     *
     * Null when the variable has not been loaded, rather than zero — "no
     * difference" and "we do not know the base" are different answers, and
     * returning zero for the second would make a scenario look like it changed
     * nothing.
     */
    public function delta(): ?Quantity
    {
        $base = $this->variable?->value;

        return $base === null ? null : $this->value->minus($base);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ScenarioVariableFactory
    {
        return ScenarioVariableFactory::new();
    }
}
