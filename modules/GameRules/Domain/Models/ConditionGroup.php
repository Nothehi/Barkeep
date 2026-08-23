<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\ConditionGroupFactory;

/**
 * Several conditions, combined by one operator.
 *
 *     All of these
 *       ├── Wood is at least 5
 *       └── Player owns Workshop
 *
 * Flat, and staying flat. Section 19 of the brief rules out nested expression
 * trees, and the restraint is deliberate rather than provisional: an arbitrary
 * tree needs a parser, a renderer and a precedence rule, and a studio that needs
 * one needs something that can evaluate it too — which is a different bounded
 * context. One `and` or one `or` over a list covers what a board game rule
 * usually says and stays readable in a form somebody fills in.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string $name
 * @property string|null $description
 * @property LogicOperator $logic_operator
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read Collection<int, RuleCondition> $conditions
 * @property-read Collection<int, ConditionGroupCondition> $memberships
 * @property-read int|null $conditions_count
 */
#[Fillable(['name', 'description'])]
class ConditionGroup extends Model
{
    /** @use HasFactory<ConditionGroupFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'logic_operator' => LogicOperator::And->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logic_operator' => LogicOperator::class,
        ];
    }

    /**
     * @return BelongsTo<RuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }

    /**
     * The conditions in the group, in the designer's order.
     *
     * @return BelongsToMany<RuleCondition, $this>
     */
    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(RuleCondition::class, 'condition_group_conditions', 'condition_group_id', 'condition_id')
            ->withPivot(['id', 'position'])
            ->orderBy('condition_group_conditions.position');
    }

    /**
     * The membership rows themselves.
     *
     * Needed as well as {@see conditions()} because reordering and removal act on
     * the row rather than on the condition — the same condition may be in several
     * groups, and detaching it from one must not touch the others.
     *
     * @return HasMany<ConditionGroupCondition, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ConditionGroupCondition::class)->orderBy('position');
    }

    /**
     * Determine whether the group belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ConditionGroupFactory
    {
        return ConditionGroupFactory::new();
    }
}
