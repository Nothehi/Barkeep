<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleEffectFactory;

/**
 * What happens once a rule or an action resolves.
 *
 *     RESOURCE  ·  Victory points  ·  +3
 *
 * Three structured fields and a sentence. Nothing here is executable, and
 * section 33 of the brief is the reason: this module defines rules, conditions,
 * effects and transitions, and it never carries any of them out. "Subtract five
 * wood" is a record of what the rulebook says, not an instruction. Whatever
 * eventually plays a game is a separate bounded context.
 *
 * The value is a string for the same reason. "+3", "-1", "half, rounded down"
 * and "all of them" are all things a rulebook says, and a numeric column would
 * refuse three of the four while implying that something adds them up.
 *
 * Exactly one owner — a rule or an action, never both and never neither.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string|null $action_id
 * @property string|null $rule_id
 * @property EffectType $effect_type
 * @property string $target
 * @property string|null $value
 * @property string|null $description
 * @property string|null $economy_resource_slug
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read RuleAction|null $action
 * @property-read GameRule|null $rule
 */
#[Fillable(['target', 'value', 'description'])]
class RuleEffect extends Model
{
    /** @use HasFactory<RuleEffectFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'effect_type' => EffectType::Custom->value,
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
            'effect_type' => EffectType::class,
            'position' => 'integer',
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
     * @return BelongsTo<RuleAction, $this>
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(RuleAction::class, 'action_id');
    }

    /**
     * @return BelongsTo<GameRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(GameRule::class, 'rule_id');
    }

    /**
     * Determine whether exactly one owner is set.
     */
    public function hasExactlyOneOwner(): bool
    {
        return ($this->action_id === null) !== ($this->rule_id === null);
    }

    /**
     * Determine whether the effect has the amount its type implies.
     */
    public function hasRequiredValue(): bool
    {
        return ! $this->effect_type->expectsValue()
            || ($this->value !== null && $this->value !== '');
    }

    /**
     * Determine whether the effect names something in the game's economy.
     */
    public function hasEconomyReference(): bool
    {
        return $this->economy_resource_slug !== null && $this->economy_resource_slug !== '';
    }

    /**
     * Determine whether the effect moves play somewhere else.
     *
     * Read by the graph builder, which draws these alongside phase transitions
     * so the flow of a game shows every way it can advance.
     */
    public function movesPlay(): bool
    {
        return $this->effect_type->movesPlay();
    }

    /**
     * Determine whether the effect belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleEffectFactory
    {
        return RuleEffectFactory::new();
    }
}
