<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\PhaseTransitionFactory;

/**
 * How play moves from one phase to the next.
 *
 *     Action phase  ──  if all players have finished  ──▶  Resolution
 *
 * An edge with an optional guard. Most transitions in a board game are
 * unconditional and automatic — the action phase simply ends and resolution
 * begins — so both `condition_id` and `trigger_id` are nullable and most rows
 * leave them so.
 *
 * The two ends must belong to the same rule set. The foreign keys cannot say
 * that, so `RuleCatalogue` resolves each phase *through* the set: a phase from
 * another rule system is never found rather than found and rejected.
 *
 * No `position` semantics beyond ordering the edges out of one phase, but that
 * ordering is a rule rather than a preference: "if somebody has won, go to game
 * end; otherwise back to round start" is two edges whose order is the whole
 * meaning.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string $from_phase_id
 * @property string $to_phase_id
 * @property string|null $condition_id
 * @property string|null $trigger_id
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 * @property-read GamePhase|null $fromPhase
 * @property-read GamePhase|null $toPhase
 * @property-read RuleCondition|null $condition
 * @property-read RuleTrigger|null $trigger
 */
class PhaseTransition extends Model
{
    /** @use HasFactory<PhaseTransitionFactory> */
    use HasFactory, HasUuids;

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
     * @return BelongsTo<RuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }

    /**
     * @return BelongsTo<GamePhase, $this>
     */
    public function fromPhase(): BelongsTo
    {
        return $this->belongsTo(GamePhase::class, 'from_phase_id');
    }

    /**
     * @return BelongsTo<GamePhase, $this>
     */
    public function toPhase(): BelongsTo
    {
        return $this->belongsTo(GamePhase::class, 'to_phase_id');
    }

    /**
     * What has to be true for play to take this edge.
     *
     * @return BelongsTo<RuleCondition, $this>
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(RuleCondition::class, 'condition_id');
    }

    /**
     * What makes it happen, when it is not simply the phase ending.
     *
     * @return BelongsTo<RuleTrigger, $this>
     */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(RuleTrigger::class, 'trigger_id');
    }

    /**
     * Determine whether anything guards this transition.
     *
     * Named `hasGuard` rather than `isGuarded`, which Eloquent already uses to
     * answer a question about mass assignment.
     *
     * An unguarded edge is the ordinary case and not a problem; it becomes one
     * only when a phase has several, which the validator notices because play
     * arriving there would have no way to choose.
     */
    public function hasGuard(): bool
    {
        return $this->condition_id !== null || $this->trigger_id !== null;
    }

    /**
     * Determine whether the transition belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PhaseTransitionFactory
    {
        return PhaseTransitionFactory::new();
    }
}
