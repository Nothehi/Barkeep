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
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleConditionFactory;

/**
 * A named, reusable logical requirement.
 *
 *     [Score] [is at least] [20]
 *     [Deck]  [is]          [empty]
 *
 * Three parts and a name. The name is why these are rows rather than columns on
 * the things that need them: "all players have passed" is written once and
 * pointed at from the phase transition, the end condition and the trigger that
 * all care about it, so changing what it means changes it everywhere.
 *
 * Declarative and never evaluated. The operator and the value sit side by side
 * as data a person reads; nothing in this module compares them, and section 47
 * of the brief specifically refuses arbitrary expressions in the builder. What
 * the validator does check is the *pairing* — an "is at least" against the word
 * "blue" is a sentence somebody typed by accident.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string $name
 * @property string|null $description
 * @property ConditionType $condition_type
 * @property ConditionOperator $operator
 * @property string|null $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read Collection<int, ConditionGroup> $groups
 * @property-read Collection<int, PhaseTransition> $transitions
 * @property-read Collection<int, VictoryCondition> $victoryConditions
 * @property-read Collection<int, DefeatCondition> $defeatConditions
 * @property-read Collection<int, GameEndCondition> $endConditions
 */
#[Fillable(['name', 'description', 'value'])]
class RuleCondition extends Model
{
    /** @use HasFactory<RuleConditionFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'condition_type' => ConditionType::Custom->value,
        'operator' => ConditionOperator::Equals->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition_type' => ConditionType::class,
            'operator' => ConditionOperator::class,
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
     * The groups this condition is part of.
     *
     * @return BelongsToMany<ConditionGroup, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ConditionGroup::class, 'condition_group_conditions', 'condition_id', 'condition_group_id')
            ->withPivot(['id', 'position'])
            ->orderBy('condition_group_conditions.position');
    }

    /**
     * The transitions this condition guards.
     *
     * @return HasMany<PhaseTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'condition_id');
    }

    /**
     * @return HasMany<VictoryCondition, $this>
     */
    public function victoryConditions(): HasMany
    {
        return $this->hasMany(VictoryCondition::class, 'condition_id');
    }

    /**
     * @return HasMany<DefeatCondition, $this>
     */
    public function defeatConditions(): HasMany
    {
        return $this->hasMany(DefeatCondition::class, 'condition_id');
    }

    /**
     * @return HasMany<GameEndCondition, $this>
     */
    public function endConditions(): HasMany
    {
        return $this->hasMany(GameEndCondition::class, 'condition_id');
    }

    /**
     * The condition read as one sentence.
     *
     * Built here rather than in a resource because three parts of it come from
     * enums that word themselves, and a client assembling it would be keeping a
     * fourth copy of the vocabulary. Used in graph edge labels and in findings,
     * both of which need the sentence rather than the fields.
     */
    public function statement(): string
    {
        $subject = $this->name;

        if (! $this->operator->expectsValue()) {
            return trim($subject.' '.$this->operator->label());
        }

        return trim($subject.' '.$this->operator->label().' '.((string) $this->value));
    }

    /**
     * Determine whether the operator has the value it needs.
     */
    public function hasRequiredValue(): bool
    {
        return ! $this->operator->expectsValue()
            || ($this->value !== null && $this->value !== '');
    }

    /**
     * Determine whether the condition belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleConditionFactory
    {
        return RuleConditionFactory::new();
    }
}
