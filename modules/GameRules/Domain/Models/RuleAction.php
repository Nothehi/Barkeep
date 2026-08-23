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
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleActionFactory;

/**
 * Something a player may do, according to the rules.
 *
 * Build, move, attack, trade, draw a card, pass. An action is where the rule
 * system meets the table: it has a phase it can be taken in, requirements that
 * gate it and effects that follow from it.
 *
 * ## This is not GameEconomy's `EconomyAction`
 *
 * The two are the module boundary's clearest case, and section 16 of the brief
 * turns on it:
 *
 *     GameRules.RuleAction      "What can the player do?"
 *     GameEconomy.EconomyAction "What does doing it cost and pay?"
 *
 * A studio that has modelled its economy will have both, describing the same
 * BUILD from two sides. They are joined by {@see $economy_action_slug} — a
 * handle, not a foreign key — and the costs are read live through the one
 * adapter allowed to talk to that module. Nothing here stores "5 wood".
 *
 * That is not a purity exercise. A copy of the cost here would be a second
 * answer to "what does building cost", and the day the two disagreed the
 * balance screen and the rules screen would each be confidently wrong.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string|null $phase_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property RuleActionType $action_type
 * @property RuleStatus $status
 * @property string|null $economy_action_slug
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read GamePhase|null $phase
 * @property-read Collection<int, RuleRequirement> $requirements
 * @property-read Collection<int, RuleEffect> $effects
 * @property-read int|null $requirements_count
 * @property-read int|null $effects_count
 */
#[Fillable(['name', 'description'])]
class RuleAction extends Model
{
    /** @use HasFactory<RuleActionFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'action_type' => RuleActionType::Basic->value,
        'status' => RuleStatus::Active->value,
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
            'action_type' => RuleActionType::class,
            'status' => RuleStatus::class,
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
     * When the action may be taken.
     *
     * @return BelongsTo<GamePhase, $this>
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(GamePhase::class, 'phase_id');
    }

    /**
     * What has to be true first.
     *
     * @return HasMany<RuleRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(RuleRequirement::class, 'action_id');
    }

    /**
     * What happens afterwards.
     *
     * @return HasMany<RuleEffect, $this>
     */
    public function effects(): HasMany
    {
        return $this->hasMany(RuleEffect::class, 'action_id');
    }

    /**
     * The action's stable handle as a value object.
     */
    public function handle(): RuleSlug
    {
        return RuleSlug::fromString($this->slug);
    }

    /**
     * Determine whether the studio has wired this action to its economy.
     */
    public function hasEconomyReference(): bool
    {
        return $this->economy_action_slug !== null && $this->economy_action_slug !== '';
    }

    /**
     * Determine whether the action still applies to play.
     */
    public function isInPlay(): bool
    {
        return $this->status->isInPlay();
    }

    /**
     * Determine whether the action belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleActionFactory
    {
        return RuleActionFactory::new();
    }
}
