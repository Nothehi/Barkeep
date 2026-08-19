<?php

namespace Modules\PrototypeIteration\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\DesignDecisionFactory;

/**
 * What the designers concluded, and why.
 *
 * The record this whole module builds towards. Changes say what was edited,
 * experiments say what was tried, playtests say what happened — and a decision
 * is the sentence somebody will read in a year to find out why the game is the
 * way it is. "Keep simultaneous combat, because players made more meaningful
 * choices and average downtime fell."
 *
 * A decision is settled rather than edited. Accepted and rejected are terminal,
 * and that is the strictest rule in the module: reversing an accepted decision
 * in place would leave the design carrying a change whose recorded
 * justification now argues against it. Studios change their minds constantly,
 * and the honest way to record that is a *new* decision in a later iteration
 * saying so — which is also how anybody reading the history would want to find
 * out. Deferred is the one ending that reopens, because "look at this again
 * after the convention" is a real answer.
 *
 * Evidence is optional and always a citation rather than a copy. A decision
 * that cites an observation points at Playtesting's observation; it does not
 * hold the text of it. Two reasons: there is then one copy of what the players
 * said, with one definition of who may see it, and a decision whose evidence
 * was copied would keep reading as supported after the evidence was corrected.
 *
 * @property string $id
 * @property string $iteration_id
 * @property string $title
 * @property string $decision
 * @property string $reason
 * @property DecisionStatus $status
 * @property string|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Iteration|null $iteration
 * @property-read User|null $decider
 * @property-read User|null $creator
 * @property-read Collection<int, DecisionEvidence> $evidence
 * @property-read int|null $evidence_count
 */
#[Fillable(['title', 'decision', 'reason'])]
class DesignDecision extends Model
{
    /** @use HasFactory<DesignDecisionFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => DecisionStatus::Proposed->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DecisionStatus::class,
            'decided_at' => 'immutable_datetime',
        ];
    }

    /**
     * The cycle this decision came out of.
     *
     * @return BelongsTo<Iteration, $this>
     */
    public function iteration(): BelongsTo
    {
        return $this->belongsTo(Iteration::class);
    }

    /**
     * The account that settled it, once somebody has.
     *
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * The account that proposed it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * What was cited in support of it.
     *
     * @return HasMany<DecisionEvidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(DecisionEvidence::class, 'decision_id');
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
     * Determine whether the decision's wording may still be changed.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the decision has been settled either way.
     */
    public function isSettled(): bool
    {
        return $this->status->isSettled();
    }

    /**
     * Determine whether the decision is still open to being settled.
     */
    public function isOpen(): bool
    {
        return ! $this->status->isTerminal();
    }

    /**
     * Determine whether the decision belongs to the given iteration.
     */
    public function belongsToIteration(Iteration $iteration): bool
    {
        return $this->iteration_id === $iteration->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignDecisionFactory
    {
        return DesignDecisionFactory::new();
    }
}
