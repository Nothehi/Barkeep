<?php

namespace Modules\PrototypeIteration\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\DesignExperimentFactory;

/**
 * A focused attempt to answer one design question.
 *
 * "Does removing the action limit increase interesting decisions?" — asked
 * before anything is run, with a method written down, and answered afterwards
 * with what actually happened and what the designer takes from it.
 *
 * The shape is the value. Four fields are filled in before the experiment runs
 * and two after, and keeping the halves apart is what stops a prediction from
 * being quietly rewritten once the result is known. That is why a completed
 * experiment refuses edits to its question, hypothesis, method and expected
 * result: an experiment whose prediction was adjusted to match its outcome has
 * proved nothing, and it is the easiest mistake in the world to make honestly.
 *
 * `actual_result` and `conclusion` are separate for the same reason at the
 * other end. "Players explored more strategies but sessions ran twenty minutes
 * longer" is an observation; "unlimited actions improve strategy but harm
 * pacing" is an argument. Only the second is a judgement, and a reader deserves
 * to see which is which.
 *
 * This is not a statistical engine. There is no sample size, no significance,
 * no A/B infrastructure — a designer running three four-player sessions is
 * doing qualitative work, and dressing it in statistics would misrepresent how
 * much it proves.
 *
 * @property string $id
 * @property string $iteration_id
 * @property string $title
 * @property string $question
 * @property string|null $hypothesis
 * @property string|null $method
 * @property string|null $expected_result
 * @property string|null $actual_result
 * @property string|null $conclusion
 * @property ExperimentStatus $status
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Iteration|null $iteration
 * @property-read User|null $creator
 */
#[Fillable(['title', 'question', 'hypothesis', 'method', 'expected_result'])]
class DesignExperiment extends Model
{
    /** @use HasFactory<DesignExperimentFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ExperimentStatus::Planned->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExperimentStatus::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The cycle this experiment belongs to.
     *
     * @return BelongsTo<Iteration, $this>
     */
    public function iteration(): BelongsTo
    {
        return $this->belongsTo(Iteration::class);
    }

    /**
     * The account that designed the experiment.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The game this belongs to, read through the cycle that owns it.
     *
     * The game is not a column here on purpose. A change, an experiment and a decision
     * all belong to exactly one iteration, and the iteration already knows its game — a
     * second copy would be a second answer that could disagree with the first after a
     * badly written import.
     *
     * The events this module dispatches carry the game id all the same, because a
     * consumer should not have to join back through two tables to find out which project
     * an event was about. This is where they get it: from the relation when it is loaded,
     * and from a single scalar read when it is not.
     */
    public function gameId(): string
    {
        return (string) $this->iteration->game_id;
    }

    /**
     * Determine whether the experiment's design may still be rewritten.
     *
     * The design is everything written down before it ran. See the note above
     * the class for why that window closes when the experiment does.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the experiment is running right now.
     */
    public function isRunning(): bool
    {
        return $this->status === ExperimentStatus::Running;
    }

    /**
     * Determine whether the experiment is over, however it ended.
     */
    public function isClosed(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Determine whether the experiment produced a result.
     */
    public function hasResult(): bool
    {
        return $this->actual_result !== null && $this->actual_result !== '';
    }

    /**
     * Determine whether the experiment belongs to the given iteration.
     */
    public function belongsToIteration(Iteration $iteration): bool
    {
        return $this->iteration_id === $iteration->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignExperimentFactory
    {
        return DesignExperimentFactory::new();
    }
}
