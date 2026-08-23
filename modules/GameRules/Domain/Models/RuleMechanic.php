<?php

namespace Modules\GameRules\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories\RuleMechanicFactory;

/**
 * A gameplay mechanism this rule system uses.
 *
 * Worker placement, deck building, push your luck. A mechanic says *what kind of
 * system exists*; the rules say *how it operates*. Both are needed and neither
 * substitutes for the other — "we use worker placement" tells a reader what
 * family of game this is in one line, and the eleven rules underneath tell them
 * how this studio's version of it works.
 *
 * ## Why this is not GameDesign's `Mechanic`
 *
 * The platform already has a model called `Mechanic`, and it is a different
 * thing: a shared, seeded vocabulary of design terms, stored in English and
 * translated on the way out, that a design record tags itself with so two
 * studios using worker placement say so with the same word.
 *
 * A row here is a mechanism present in *one game's rule system*, named in that
 * studio's own words and sitting beside the rules that operate it. It is not
 * curated, not shared and not translated — a designer's "engine of small
 * regrets" is a mechanic here and would never belong in the catalogue.
 *
 * Section 10 of the module brief calls this entity `Mechanic`. The prefix is the
 * one place this module departs from its own naming, and it is because the
 * shorter name was already taken by something that means something else.
 *
 * @property string $id
 * @property string $rule_set_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property MechanicCategory $category
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read RuleSet|null $ruleSet
 */
#[Fillable(['name', 'description'])]
class RuleMechanic extends Model
{
    /** @use HasFactory<RuleMechanicFactory> */
    use HasFactory, HasUuids;

    /**
     * The table backing the model.
     *
     * Stated because the prefix is load-bearing rather than stylistic, and
     * leaving Eloquent to derive it would make the pairing look accidental.
     *
     * @var string
     */
    protected $table = 'rule_mechanics';

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => MechanicCategory::Other->value,
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
            'category' => MechanicCategory::class,
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
     * The mechanic's stable handle as a value object.
     */
    public function handle(): RuleSlug
    {
        return RuleSlug::fromString($this->slug);
    }

    /**
     * Determine whether the mechanic belongs to the given set.
     */
    public function belongsToRuleSet(RuleSet $ruleSet): bool
    {
        return $this->rule_set_id === $ruleSet->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): RuleMechanicFactory
    {
        return RuleMechanicFactory::new();
    }
}
