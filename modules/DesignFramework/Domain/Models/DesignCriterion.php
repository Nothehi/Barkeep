<?php

namespace Modules\DesignFramework\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\DesignCriterionFactory;

/**
 * A question a designer answers about their own game.
 *
 * "Does the game provide meaningful decisions?" "Is the core loop
 * understandable?" "Is downtime acceptable?"
 *
 * The distinction that matters most in this module lives here: a criterion holds
 * no score. It is the question, asked identically of every game following the
 * version it belongs to. What a particular game answered is a
 * {@see CriterionEvaluation}, hung off that game's adoption of the framework.
 *
 * Storing a rating on the criterion instead would mean every studio on v1 shared
 * one assessment — which is such a natural-looking mistake that section 22 calls
 * it out explicitly, and the `design_criteria` table has no rating column to
 * make it with.
 *
 * @property string|null $description
 * @property-read Collection<int, CriterionEvaluation> $evaluations
 */
#[Fillable(['title', 'description'])]
class DesignCriterion extends PhaseContent
{
    /** @use HasFactory<DesignCriterionFactory> */
    use HasFactory;

    /**
     * The table backing the model.
     *
     * Declared because Eloquent would pluralise "criterion" into
     * "design_criterions".
     *
     * @var string
     */
    protected $table = 'design_criteria';

    /**
     * Every game's answer to this question.
     *
     * Rarely useful from this direction — evaluations are read through a game's
     * adoption, not through the criterion — but it is what makes "how many games
     * think their core loop is weak?" answerable when somebody eventually asks.
     *
     * @return HasMany<CriterionEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(CriterionEvaluation::class, 'criterion_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignCriterionFactory
    {
        return DesignCriterionFactory::new();
    }
}
