<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GameRules\Domain\Enums\TriggerType;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleTriggerFactory;

/**
 * When something happens without anybody choosing it.
 *
 * "At the start of a round." "When a player reaches ten points." "When the deck
 * runs out."
 *
 * This module records that the trigger exists and what points at it. It never
 * fires one, and section 23 of the brief is explicit about that. It is also the
 * line most likely to be crossed by accident: a trigger sitting next to an
 * effect looks like something that wants to be run, and the first `if` written
 * to run it is the first line of a game engine living inside a design tool.
 *
 * Which is why there is no `fires_effect_id` column and no join table to one.
 * What a trigger guards is expressed the other way round — a phase transition
 * names the trigger that moves it — so the data has no shape an execution loop
 * could walk.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string $name
 * @property string|null $description
 * @property TriggerType $trigger_type
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read Collection<int, PhaseTransition> $transitions
 */
#[Fillable(['name', 'description'])]
class RuleTrigger extends Model
{
    /** @use HasFactory<RuleTriggerFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'trigger_type' => TriggerType::Custom->value,
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
            'trigger_type' => TriggerType::class,
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
     * The phase transitions this trigger moves.
     *
     * @return HasMany<PhaseTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'trigger_id');
    }

    /**
     * Determine whether the trigger belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleTriggerFactory
    {
        return RuleTriggerFactory::new();
    }
}
