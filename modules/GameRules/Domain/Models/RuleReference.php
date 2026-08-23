<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleReferenceFactory;

/**
 * One rule's relationship to another.
 *
 *     Siege  ── exception to ──▶  Combat
 *     Combat ── overrides    ──▶  Movement
 *
 * These are the facts a designer loses first and needs most. "Siege is an
 * exception to Combat" lives in somebody's head until the day they rewrite
 * Combat, and writing it down is what lets a screen answer "what breaks if I
 * change this?" before a playtester does.
 *
 * Both rules must belong to the same rule set, which is why this table has no
 * `rule_set_id` of its own: the set is reachable through either end, and a third
 * copy would be a third thing to keep in step. The pairing is proved by
 * resolving the referenced rule *through* the referring rule's set.
 *
 * A cycle among the directed kinds — depends on, modifies, overrides, exception
 * to — is refused, because neither rule could then be read first. `related to`
 * is symmetric and carries no order, so a mutual one is a note rather than a
 * contradiction.
 *
 * @property string $id
 * @property string $rule_id
 * @property string $referenced_rule_id
 * @property ReferenceType $reference_type
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameRule|null $rule
 * @property-read GameRule|null $referencedRule
 */
#[Fillable(['description'])]
class RuleReference extends Model
{
    /** @use HasFactory<RuleReferenceFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'reference_type' => ReferenceType::RelatedTo->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reference_type' => ReferenceType::class,
        ];
    }

    /**
     * The rule doing the referring.
     *
     * @return BelongsTo<GameRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(GameRule::class, 'rule_id');
    }

    /**
     * The rule being referred to.
     *
     * @return BelongsTo<GameRule, $this>
     */
    public function referencedRule(): BelongsTo
    {
        return $this->belongsTo(GameRule::class, 'referenced_rule_id');
    }

    /**
     * Determine whether the relationship has a direction that matters.
     *
     * Only the directed kinds are followed when looking for a cycle.
     */
    public function isDirected(): bool
    {
        return $this->reference_type->isDirected();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleReferenceFactory
    {
        return RuleReferenceFactory::new();
    }
}
