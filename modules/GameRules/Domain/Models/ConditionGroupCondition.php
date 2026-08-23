<?php

namespace Modules\GameRules\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One condition's place in one group.
 *
 * A model rather than an anonymous pivot, because the row has an identity worth
 * addressing: a condition can be in several groups, and removing it from one is
 * a change to *this* membership rather than to the condition. Giving the row a
 * uuid is what lets a route name it.
 *
 * No timestamps. A membership is a position in a list; when somebody put it
 * there is not a fact anybody has asked for, and the columns would be two more
 * things the cloner has to decide about.
 *
 * @property string $id
 * @property string $condition_group_id
 * @property string $condition_id
 * @property int $position
 * @property-read ConditionGroup|null $group
 * @property-read RuleCondition|null $condition
 */
class ConditionGroupCondition extends Model
{
    use HasUuids;

    /**
     * The table backing the model.
     *
     * @var string
     */
    protected $table = 'condition_group_conditions';

    /**
     * Membership rows carry no timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
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
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ConditionGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ConditionGroup::class, 'condition_group_id');
    }

    /**
     * @return BelongsTo<RuleCondition, $this>
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(RuleCondition::class, 'condition_id');
    }
}
