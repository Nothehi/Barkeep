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
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\GameRuleFactory;
use Modules\Identity\Domain\Models\User;

/**
 * One explicit rule in a game's rule system.
 *
 * Rules nest, because rulebooks do:
 *
 *     Combat
 *       ├── Players declare attacks
 *       ├── Defender chooses defence
 *       ├── Resolve combat
 *       └── Apply damage
 *
 * The nesting is a plain `parent_rule_id` with no depth column and no
 * materialised path, which is the right trade at this size: a rulebook has tens
 * of rules, not millions, so the tree is assembled in memory from one flat query
 * and there is no second representation to keep in step.
 *
 * What the schema cannot hold is the promise that following the parents
 * terminates. `CycleDetector` refuses a cycle on the way in and the validator
 * reports any that predate the check — see section 54 of the brief.
 *
 * Named `GameRule` rather than `Rule` because `Rule` is Laravel's validation
 * facade, and a model that shadowed it in every file that imported both would be
 * a trap rather than a convenience.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string|null $parent_rule_id
 * @property string|null $phase_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property RuleType $rule_type
 * @property RuleStatus $status
 * @property int $position
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read GameRule|null $parent
 * @property-read GamePhase|null $phase
 * @property-read User|null $creator
 * @property-read Collection<int, GameRule> $children
 * @property-read Collection<int, RuleRequirement> $requirements
 * @property-read Collection<int, RuleEffect> $effects
 * @property-read Collection<int, RuleReference> $references
 * @property-read Collection<int, RuleReference> $referencedBy
 * @property-read int|null $children_count
 * @property-read int|null $requirements_count
 * @property-read int|null $effects_count
 * @property-read int|null $references_count
 */
#[Fillable(['name', 'description'])]
class GameRule extends Model
{
    /** @use HasFactory<GameRuleFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'rule_type' => RuleType::General->value,
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
            'rule_type' => RuleType::class,
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
     * The rule this one sits under, if any.
     *
     * @return BelongsTo<GameRule, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GameRule::class, 'parent_rule_id');
    }

    /**
     * The rules that sit under this one, in the designer's order.
     *
     * @return HasMany<GameRule, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(GameRule::class, 'parent_rule_id')->orderBy('position')->orderBy('name');
    }

    /**
     * The phase this rule applies during, if it applies to one.
     *
     * @return BelongsTo<GamePhase, $this>
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(GamePhase::class, 'phase_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * What has to be true for the rule to apply.
     *
     * @return HasMany<RuleRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(RuleRequirement::class, 'rule_id');
    }

    /**
     * What happens when it does.
     *
     * @return HasMany<RuleEffect, $this>
     */
    public function effects(): HasMany
    {
        return $this->hasMany(RuleEffect::class, 'rule_id');
    }

    /**
     * The rules this one points at.
     *
     * @return HasMany<RuleReference, $this>
     */
    public function references(): HasMany
    {
        return $this->hasMany(RuleReference::class, 'rule_id');
    }

    /**
     * The rules that point at this one.
     *
     * Worth having as its own relation rather than as a query: "what breaks if I
     * change this?" is the question a designer asks before editing a rule, and
     * it is answered by this side of the edge.
     *
     * @return HasMany<RuleReference, $this>
     */
    public function referencedBy(): HasMany
    {
        return $this->hasMany(RuleReference::class, 'referenced_rule_id');
    }

    /**
     * The rule's stable handle as a value object.
     */
    public function handle(): RuleSlug
    {
        return RuleSlug::fromString($this->slug);
    }

    /**
     * Determine whether the rule belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Determine whether the rule still applies to play.
     */
    public function isInPlay(): bool
    {
        return $this->status->isInPlay();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GameRuleFactory
    {
        return GameRuleFactory::new();
    }
}
