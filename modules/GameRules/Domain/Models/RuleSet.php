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
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleSetFactory;
use Modules\Identity\Domain\Models\User;

/**
 * The complete rule system of a game, at one point in its design.
 *
 * A rule set is the aggregate everything else in this module hangs off. Rules,
 * mechanics, phases, transitions, actions, requirements, conditions, groups,
 * effects, triggers, victory, defeat and end conditions and rule references all
 * belong to one, which is what makes "freeze the rules" and "clone the rules"
 * single operations rather than fourteen.
 *
 * It belongs to a `GameVersion` and never to a `Game`, and that is the module's
 * foundational decision rather than a schema detail. Combat was resolved with one
 * die in v1 and two in v2; if the rules hung off the game, the second answer
 * would overwrite the first and every playtest run against v1 would become
 * uninterpretable.
 *
 * A version may carry several rule sets: drafts being written, the one in play,
 * and the archived ones that came before. Exactly one may be active, which the
 * database enforces with a partial unique index rather than this class enforcing
 * it in PHP.
 *
 * ## What makes this different from a balance profile
 *
 * The two look alike and behave differently in one important way. An active
 * balance profile is still editable — tuning is what a studio does to the numbers
 * in play. An active rule set is not: the rules are what a session was played
 * under, so changing them rewrites what every playtest against them means. The
 * way forward is a clone, which is why {@see clonedFrom()} exists and why
 * `isModifiable()` is true only for a draft.
 *
 * @property string $id
 * @property string $game_version_id
 * @property string $name
 * @property string|null $description
 * @property RuleSetStatus $status
 * @property string|null $cloned_from_rule_set_id
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameVersion|null $version
 * @property-read User|null $creator
 * @property-read RuleSet|null $clonedFrom
 * @property-read Collection<int, RuleSet> $clones
 * @property-read Collection<int, GameRule> $rules
 * @property-read Collection<int, RuleMechanic> $mechanics
 * @property-read Collection<int, GamePhase> $phases
 * @property-read Collection<int, PhaseTransition> $transitions
 * @property-read Collection<int, RuleAction> $actions
 * @property-read Collection<int, RuleRequirement> $requirements
 * @property-read Collection<int, RuleCondition> $conditions
 * @property-read Collection<int, ConditionGroup> $conditionGroups
 * @property-read Collection<int, RuleEffect> $effects
 * @property-read Collection<int, RuleTrigger> $triggers
 * @property-read Collection<int, VictoryCondition> $victoryConditions
 * @property-read Collection<int, DefeatCondition> $defeatConditions
 * @property-read Collection<int, GameEndCondition> $endConditions
 * @property-read int|null $rules_count
 * @property-read int|null $mechanics_count
 * @property-read int|null $phases_count
 * @property-read int|null $transitions_count
 * @property-read int|null $actions_count
 * @property-read int|null $conditions_count
 * @property-read int|null $effects_count
 * @property-read int|null $triggers_count
 * @property-read int|null $victory_conditions_count
 * @property-read int|null $defeat_conditions_count
 * @property-read int|null $end_conditions_count
 */
#[Fillable(['name', 'description'])]
class RuleSet extends Model
{
    /** @use HasFactory<RuleSetFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => RuleSetStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RuleSetStatus::class,
        ];
    }

    /**
     * The design state this rule set describes.
     *
     * @return BelongsTo<GameVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }

    /**
     * The account that started writing it down.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The set this one was cloned from, when it was cloned rather than written.
     *
     * @return BelongsTo<RuleSet, $this>
     */
    public function clonedFrom(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class, 'cloned_from_rule_set_id');
    }

    /**
     * The sets cloned from this one.
     *
     * @return HasMany<RuleSet, $this>
     */
    public function clones(): HasMany
    {
        return $this->hasMany(RuleSet::class, 'cloned_from_rule_set_id');
    }

    /**
     * Every rule in the set, at every depth.
     *
     * Flat on purpose. The tree is assembled from `parent_rule_id` by whoever
     * needs it, so that loading "all the rules" is one query rather than one per
     * level — and so that a cycle in the data cannot make a relation recurse
     * forever.
     *
     * @return HasMany<GameRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(GameRule::class);
    }

    /**
     * The rules with no parent: the top level of the rulebook.
     *
     * @return HasMany<GameRule, $this>
     */
    public function rootRules(): HasMany
    {
        return $this->rules()->whereNull('parent_rule_id');
    }

    /**
     * @return HasMany<RuleMechanic, $this>
     */
    public function mechanics(): HasMany
    {
        return $this->hasMany(RuleMechanic::class);
    }

    /**
     * @return HasMany<GamePhase, $this>
     */
    public function phases(): HasMany
    {
        return $this->hasMany(GamePhase::class);
    }

    /**
     * @return HasMany<PhaseTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(PhaseTransition::class);
    }

    /**
     * @return HasMany<RuleAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class);
    }

    /**
     * @return HasMany<RuleRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(RuleRequirement::class);
    }

    /**
     * @return HasMany<RuleCondition, $this>
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(RuleCondition::class);
    }

    /**
     * @return HasMany<ConditionGroup, $this>
     */
    public function conditionGroups(): HasMany
    {
        return $this->hasMany(ConditionGroup::class);
    }

    /**
     * @return HasMany<RuleEffect, $this>
     */
    public function effects(): HasMany
    {
        return $this->hasMany(RuleEffect::class);
    }

    /**
     * @return HasMany<RuleTrigger, $this>
     */
    public function triggers(): HasMany
    {
        return $this->hasMany(RuleTrigger::class);
    }

    /**
     * @return HasMany<VictoryCondition, $this>
     */
    public function victoryConditions(): HasMany
    {
        return $this->hasMany(VictoryCondition::class);
    }

    /**
     * @return HasMany<DefeatCondition, $this>
     */
    public function defeatConditions(): HasMany
    {
        return $this->hasMany(DefeatCondition::class);
    }

    /**
     * @return HasMany<GameEndCondition, $this>
     */
    public function endConditions(): HasMany
    {
        return $this->hasMany(GameEndCondition::class);
    }

    /**
     * Determine whether the rules inside the set may still be edited.
     *
     * Only answers for the rule set. Whether the game around it is still
     * accepting changes is a separate question, and both have to be true — see
     * the policy and the guard, which check each in turn.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the set's own name and description may still change.
     *
     * Looser than {@see isModifiable()}: correcting the title of the rule system
     * a session was played under does not change what was played.
     */
    public function isRenamable(): bool
    {
        return $this->status->allowsRenaming();
    }

    /**
     * Determine whether this is the rule system currently in play.
     */
    public function isActive(): bool
    {
        return $this->status === RuleSetStatus::Active;
    }

    /**
     * Determine whether the rule set has been put away.
     */
    public function isArchived(): bool
    {
        return $this->status === RuleSetStatus::Archived;
    }

    /**
     * Determine whether the rule set describes the given design state.
     *
     * Used where a version has been resolved separately from the set, so that
     * the two are proved to match rather than assumed to.
     */
    public function belongsToVersion(GameVersion $version): bool
    {
        return $this->game_version_id === $version->getKey();
    }

    /**
     * Determine whether the rule set ultimately belongs to the given game.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->version?->game_id === $game->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleSetFactory
    {
        return RuleSetFactory::new();
    }
}
