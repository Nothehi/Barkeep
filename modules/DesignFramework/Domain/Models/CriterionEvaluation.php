<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\CriterionEvaluationFactory;
use Modules\Identity\Domain\Models\User;

/**
 * One game's assessment of itself against one criterion.
 *
 * Belongs to the game's *adoption* of the framework, not to the criterion and not
 * to the game. That is section 22's rule and the module's most important
 * separation: the criterion is a question asked of everybody following the
 * version, and this is one studio's answer.
 *
 * Hanging it off the adoption rather than off the game has a second benefit
 * beyond correctness: the answer is automatically scoped to the version that
 * asked the question, so nothing has to check that the two agree at read time.
 *
 * One standing answer per criterion per game. Re-evaluating overwrites, because a
 * criterion asks how the design is *now* — a history of grades over time would be
 * a genuinely useful feature and it would need its own table, not a relaxation of
 * this one's unique index.
 *
 * @property string $id
 * @property string $game_framework_id
 * @property string $criterion_id
 * @property CriterionRating $status
 * @property string|null $notes
 * @property string $evaluated_by
 * @property CarbonImmutable $evaluated_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameFramework|null $gameFramework
 * @property-read DesignCriterion|null $criterion
 * @property-read User|null $evaluator
 */
#[Fillable(['notes'])]
class CriterionEvaluation extends Model
{
    /** @use HasFactory<CriterionEvaluationFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => CriterionRating::NotEvaluated->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CriterionRating::class,
            'evaluated_at' => 'immutable_datetime',
        ];
    }

    /**
     * The adoption this assessment belongs to.
     *
     * @return BelongsTo<GameFramework, $this>
     */
    public function gameFramework(): BelongsTo
    {
        return $this->belongsTo(GameFramework::class);
    }

    /**
     * The question being answered.
     *
     * @return BelongsTo<DesignCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(DesignCriterion::class, 'criterion_id');
    }

    /**
     * The account that made the assessment.
     *
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * Determine whether a judgement has actually been made.
     *
     * A row whose status is still "not evaluated" is unusual — the command that
     * writes one requires a grade — but the enum has the case and the model
     * answers for it rather than leaving callers to compare against a value.
     */
    public function isEvaluated(): bool
    {
        return $this->status->isEvaluated();
    }

    /**
     * Determine whether the designer is satisfied with this aspect.
     */
    public function isSatisfactory(): bool
    {
        return $this->status->isSatisfactory();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CriterionEvaluationFactory
    {
        return CriterionEvaluationFactory::new();
    }
}
