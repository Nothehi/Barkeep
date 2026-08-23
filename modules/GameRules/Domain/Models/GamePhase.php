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
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\GamePhaseFactory;

/**
 * A stage of the game as it is played.
 *
 * Setup, round start, the action phase, resolution, cleanup, game end. Phases
 * nest — a turn inside a round — and are ordered, and the order means something:
 * a turn structure read out of sequence is a different turn structure.
 *
 * ## Not DesignFramework's `DesignPhase`
 *
 * The distinction is the sharpest in the module and the easiest to lose. A
 * `GamePhase` is a stage of *play*: it is what a player is doing at the table. A
 * `DesignPhase` is a stage of the *designer's work*: ideation, prototyping,
 * playtesting.
 *
 *     DesignFramework  "At what stage should the designer work?"
 *     GameRules        "At what stage of the actual game does this happen?"
 *
 * The two modules do not import one another and neither knows the other exists.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string|null $parent_phase_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property GamePhaseType $phase_type
 * @property RuleStatus $status
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read GamePhase|null $parent
 * @property-read Collection<int, GamePhase> $children
 * @property-read Collection<int, RuleAction> $actions
 * @property-read Collection<int, GameRule> $rules
 * @property-read Collection<int, PhaseTransition> $outgoing
 * @property-read Collection<int, PhaseTransition> $incoming
 * @property-read int|null $children_count
 * @property-read int|null $actions_count
 * @property-read int|null $rules_count
 */
#[Fillable(['name', 'description'])]
class GamePhase extends Model
{
    /** @use HasFactory<GamePhaseFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'phase_type' => GamePhaseType::Round->value,
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
            'phase_type' => GamePhaseType::class,
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
     * The phase this one sits inside, if any.
     *
     * @return BelongsTo<GamePhase, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(GamePhase::class, 'parent_phase_id');
    }

    /**
     * @return HasMany<GamePhase, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(GamePhase::class, 'parent_phase_id')->orderBy('position')->orderBy('name');
    }

    /**
     * What players may do during this phase.
     *
     * @return HasMany<RuleAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class, 'phase_id');
    }

    /**
     * The rules that apply during this phase.
     *
     * @return HasMany<GameRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(GameRule::class, 'phase_id');
    }

    /**
     * Where play can go from here.
     *
     * @return HasMany<PhaseTransition, $this>
     */
    public function outgoing(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'from_phase_id')->orderBy('position');
    }

    /**
     * Where play can arrive from.
     *
     * @return HasMany<PhaseTransition, $this>
     */
    public function incoming(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'to_phase_id')->orderBy('position');
    }

    /**
     * The phase's stable handle as a value object.
     */
    public function handle(): RuleSlug
    {
        return RuleSlug::fromString($this->slug);
    }

    /**
     * Determine whether this phase is still part of the game.
     *
     * A deprecated phase stays in the list, greyed, so the record of having tried
     * it survives — and the validator skips it, because a phase kept for the
     * record does not need an exit.
     */
    public function isInPlay(): bool
    {
        return $this->status->isInPlay();
    }

    /**
     * Determine whether play is meant to stop here.
     */
    public function isTerminal(): bool
    {
        return $this->phase_type->isTerminal();
    }

    /**
     * Determine whether play is meant to begin here.
     */
    public function isEntry(): bool
    {
        return $this->phase_type->isEntry();
    }

    /**
     * Determine whether the phase belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GamePhaseFactory
    {
        return GamePhaseFactory::new();
    }
}
