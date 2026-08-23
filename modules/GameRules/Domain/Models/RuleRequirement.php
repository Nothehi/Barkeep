<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleRequirementFactory;

/**
 * What has to be true before a rule or an action applies.
 *
 * "You hold at least five wood." "You own the workshop." "It is your turn."
 *
 * Prose with a category, not an expression — section 17 of the brief refuses a
 * scripting language, and the reason is worth keeping in front of whoever
 * extends this: the moment a requirement becomes evaluable, something has to
 * evaluate it, and the module stops describing a board game and starts being a
 * half-finished engine for one.
 *
 * Exactly one owner. A requirement belongs to a rule or to an action, never both
 * and never neither — the schema cannot say that portably, so the commands do,
 * and the validator reports the shapes that predate the check.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string|null $action_id
 * @property string|null $rule_id
 * @property RequirementType $requirement_type
 * @property string $description
 * @property string|null $value
 * @property string|null $economy_resource_slug
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read RuleAction|null $action
 * @property-read GameRule|null $rule
 */
#[Fillable(['description', 'value'])]
class RuleRequirement extends Model
{
    /** @use HasFactory<RuleRequirementFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'requirement_type' => RequirementType::Custom->value,
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
            'requirement_type' => RequirementType::class,
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
     *
     * The invariant the schema cannot hold. Read by the commands before a write
     * and by the validator afterwards, so both ask the same question of the same
     * object.
     */
    public function hasExactlyOneOwner(): bool
    {
        return ($this->action_id === null) !== ($this->rule_id === null);
    }

    /**
     * Determine whether the requirement is priced in the game's economy.
     */
    public function hasEconomyReference(): bool
    {
        return $this->economy_resource_slug !== null && $this->economy_resource_slug !== '';
    }

    /**
     * Determine whether the requirement belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleRequirementFactory
    {
        return RuleRequirementFactory::new();
    }
}
