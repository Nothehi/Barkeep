<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\GameRules\Domain\Models\Concerns\DescribesAnOutcome;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\VictoryConditionFactory;

/**
 * A way to win the game.
 *
 *     First player to reach 20 victory points.
 *     Highest score after eight rounds.
 *     Control three territories at the end of a round.
 *
 * One of three outcome models that stay deliberately separate. Winning, losing
 * and the game being over are three different questions, and a game routinely
 * answers all three at once: the round eight marker ends it, the highest score
 * wins it, and a player at zero health lost it two rounds ago. See
 * {@see DescribesAnOutcome} for why they share a trait rather than a table.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string $name
 * @property string|null $description
 * @property string|null $condition_id
 * @property int $priority
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read RuleCondition|null $condition
 */
#[Fillable(['name', 'description'])]
class VictoryCondition extends Model
{
    /** @use HasFactory<VictoryConditionFactory> */
    use DescribesAnOutcome, HasFactory, HasUuids;

    /**
     * The table backing the model.
     *
     * @var string
     */
    protected $table = 'victory_conditions';

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => 'integer',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): VictoryConditionFactory
    {
        return VictoryConditionFactory::new();
    }
}
