<?php

namespace Modules\GameRules\Domain\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * The parts a victory, defeat and end condition have in common.
 *
 * The three stay separate models, and section 26 of the brief is firm that they
 * must: "the round eight marker is reached", "you drop to zero health" and "you
 * are first to twenty points" are three different questions, and a game
 * routinely answers all three at once. Collapsing them into one table with a
 * `kind` column would make "which of these ends the game and which of these wins
 * it" a filter rather than a fact, and every screen would then have to remember
 * to apply it.
 *
 * What they share is genuinely shared: a name, a description, an optional
 * condition and a priority. That belongs in one place, which is here — a trait
 * rather than a base class, so the three remain unrelated types that no query
 * can accidentally mix.
 *
 * @property string $rule_set_id
 * @property string|null $condition_id
 * @property int $priority
 */
trait DescribesAnOutcome
{
    /**
     * @return BelongsTo<RuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RuleSet::class);
    }

    /**
     * What makes it true, when the studio has stated it precisely.
     *
     * @return BelongsTo<RuleCondition, $this>
     */
    public function condition(): BelongsTo
    {
        return $this->belongsTo(RuleCondition::class, 'condition_id');
    }

    /**
     * Determine whether anybody can tell when this has been met.
     *
     * An outcome with no condition is written down long before it is defined —
     * "whoever has the most points" goes in on day one. The validator says so and
     * nothing refuses it.
     */
    public function isMeasurable(): bool
    {
        return $this->condition_id !== null;
    }

    /**
     * Determine whether the outcome belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }
}
