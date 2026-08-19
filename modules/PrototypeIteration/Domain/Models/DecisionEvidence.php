<?php

namespace Modules\PrototypeIteration\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\DecisionEvidenceFactory;

/**
 * One thing cited in support of a decision.
 *
 * A citation, never a copy. "Players spent less time waiting" belongs to the
 * observation in Playtesting; what lives here is the fact that somebody thought
 * that observation supported this decision, plus their reason for thinking so.
 *
 * The reference is deliberately weak: a type and a bare id, with no foreign key
 * and no stored class name. That is the price of not duplicating the evidence,
 * and it buys a lot — Playtesting stays the single home of what the players
 * said, with one definition of who may read it, and this module never has to be
 * migrated when another context reshapes its tables.
 *
 * The cost is that the pointer can dangle. A citation whose target has been
 * removed renders as a citation whose target is gone, which is honest and is
 * better than the alternative: a cascade that deleted the argument along with
 * the exhibit. Resolution happens through whoever owns the type, scoped to the
 * same game, so a reference cannot be used to reach across a workspace
 * boundary — see `PlaytestEvidence` for the playtesting side.
 *
 * A {@see EvidenceType::Note} has no reference at all. It *is* the evidence
 * rather than a pointer to it, and it exists so that "Marco's group told us the
 * same thing at the fair" can be recorded without inventing a record for it.
 *
 * @property string $id
 * @property string $decision_id
 * @property EvidenceType $type
 * @property string|null $reference_id
 * @property string|null $description
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read DesignDecision|null $decision
 * @property-read User|null $creator
 */
#[Fillable(['description'])]
class DecisionEvidence extends Model
{
    /** @use HasFactory<DecisionEvidenceFactory> */
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * Named explicitly because "evidence" is already a mass noun and
     * `decision_evidences` is not a word anybody would type.
     *
     * @var string
     */
    protected $table = 'decision_evidence';

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => EvidenceType::Note->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EvidenceType::class,
        ];
    }

    /**
     * The decision this supports.
     *
     * @return BelongsTo<DesignDecision, $this>
     */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(DesignDecision::class, 'decision_id');
    }

    /**
     * The account that cited it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine whether this citation points at a record somewhere else.
     */
    public function pointsAtRecord(): bool
    {
        return $this->type->requiresReference() && $this->reference_id !== null;
    }

    /**
     * Determine whether the cited record is owned by Playtesting.
     */
    public function citesPlaytesting(): bool
    {
        return $this->type->belongsToPlaytesting();
    }

    /**
     * Determine whether the evidence belongs to the given decision.
     */
    public function belongsToDecision(DesignDecision $decision): bool
    {
        return $this->decision_id === $decision->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DecisionEvidenceFactory
    {
        return DecisionEvidenceFactory::new();
    }
}
