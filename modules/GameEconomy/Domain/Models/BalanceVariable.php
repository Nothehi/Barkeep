<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts\AsQuantity;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\BalanceVariableFactory;

/**
 * One number a designer tunes.
 *
 * Starting gold is 10. Wood production is 3. The victory threshold is 20. These
 * are the knobs — the things somebody changes between playtests and then wants
 * to know whether changing helped.
 *
 * A variable may be *about* a resource, an action, both or neither, which is why
 * there are two nullable foreign keys rather than a polymorphic pair. There are
 * exactly two things it can point at, and a real foreign key on each is worth
 * more than the flexibility a `*_type`/`*_id` column would buy: the database
 * refuses a variable pointing at a resource that has been removed, where a
 * polymorphic reference would leave it dangling and the analysis reading a
 * number about nothing.
 *
 * The value here is the base. A scenario may state a different one — see
 * {@see ScenarioVariable}, which is a separate row in a separate table precisely
 * so that overriding can never write here.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string|null $resource_type_id
 * @property string|null $action_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property Quantity $value
 * @property string|null $unit
 * @property Quantity|null $min_value
 * @property Quantity|null $max_value
 * @property Quantity|null $step
 * @property BalanceVariableCategory $category
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read ResourceType|null $resource
 * @property-read EconomyAction|null $action
 * @property-read Collection<int, ScenarioVariable> $overrides
 * @property-read int|null $overrides_count
 */
#[Fillable(['name', 'description', 'unit'])]
class BalanceVariable extends Model
{
    /** @use HasFactory<BalanceVariableFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'value' => 0,
        'category' => BalanceVariableCategory::Other->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => AsQuantity::class,
            'min_value' => AsQuantity::class,
            'max_value' => AsQuantity::class,
            'step' => AsQuantity::class,
            'category' => BalanceVariableCategory::class,
        ];
    }

    /**
     * @return BelongsTo<BalanceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(BalanceProfile::class, 'balance_profile_id');
    }

    /**
     * The resource this number is about, where it is about one.
     *
     * @return BelongsTo<ResourceType, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    /**
     * The action this number is about, where it is about one.
     *
     * @return BelongsTo<EconomyAction, $this>
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(EconomyAction::class, 'action_id');
    }

    /**
     * Every scenario that states a different value for this variable.
     *
     * @return HasMany<ScenarioVariable, $this>
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(ScenarioVariable::class, 'balance_variable_id');
    }

    /**
     * The variable's stable handle as a value object.
     */
    public function handle(): EconomySlug
    {
        return EconomySlug::fromString($this->slug);
    }

    /**
     * Determine whether the base value falls inside its own declared range.
     *
     * Asked by the analysis rather than enforced on save, which is section 31 of
     * the brief: a designer narrowing a range around a value they are about to
     * change should be told, not stopped.
     */
    public function isWithinItsRange(): bool
    {
        return $this->value->isWithin($this->min_value, $this->max_value);
    }

    /**
     * Determine whether this number is a proportion rather than a count.
     *
     * Probabilities are written between 0 and 1 throughout the module, never as
     * percentages. Mixing the two scales within one concept is how a game ends
     * up with one variable at 0.25 and another at 25, and somebody eventually
     * multiplying the wrong pair.
     */
    public function isProbability(): bool
    {
        return $this->category->isProportion();
    }

    /**
     * Determine whether a probability is written on the scale the module uses.
     */
    public function isWellFormedProbability(): bool
    {
        if (! $this->isProbability()) {
            return true;
        }

        return $this->value->isWithin(Quantity::from(0), Quantity::from(1));
    }

    /**
     * Determine whether the variable belongs to the given profile.
     */
    public function belongsToProfile(BalanceProfile $profile): bool
    {
        return $this->balance_profile_id === $profile->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BalanceVariableFactory
    {
        return BalanceVariableFactory::new();
    }
}
