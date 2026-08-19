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
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\EconomyActionFactory;

/**
 * Something a player or the game does that moves the economy.
 *
 * Build, harvest, trade, place a worker, draw a card. An action is the join
 * between resources: it is where a cost meets a reward, and it is what makes
 * "two wood makes one gold" a fact about this game rather than an assumption the
 * platform invented.
 *
 * Named `EconomyAction` rather than `Action` on purpose. "Action" is one of the
 * most overloaded words in both this codebase and the domain — the framework has
 * controller actions, `ResourceCategory` has an `Action` case for action points,
 * and a bare `Action` model would be ambiguous in every file that imported it.
 *
 * What it does is spread across three child tables rather than held here:
 * {@see costs()} and {@see rewards()} are the quantities, and {@see effects()}
 * is everything that has no quantity. That split is what lets the analysis sum
 * the first two and leave the third alone.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $position
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read Collection<int, ActionCost> $costs
 * @property-read Collection<int, ActionReward> $rewards
 * @property-read Collection<int, ActionEffect> $effects
 * @property-read int|null $costs_count
 * @property-read int|null $rewards_count
 * @property-read int|null $effects_count
 */
#[Fillable(['name', 'description'])]
class EconomyAction extends Model
{
    /** @use HasFactory<EconomyActionFactory> */
    use HasFactory, HasUuids;

    /**
     * The table backing the model.
     *
     * Stated because the class is named for clarity in code and the table is
     * named for clarity in the database; Eloquent's own derivation would look
     * for `economy_actions` from `EconomyAction` and happens to agree, but
     * leaving it implicit would make the pairing look accidental.
     *
     * @var string
     */
    protected $table = 'economy_actions';

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
     * @return BelongsTo<BalanceProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(BalanceProfile::class, 'balance_profile_id');
    }

    /**
     * What the action takes to perform.
     *
     * @return HasMany<ActionCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(ActionCost::class, 'action_id');
    }

    /**
     * What the action pays out.
     *
     * @return HasMany<ActionReward, $this>
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ActionReward::class, 'action_id');
    }

    /**
     * What the action does that is not a quantity of a resource.
     *
     * @return HasMany<ActionEffect, $this>
     */
    public function effects(): HasMany
    {
        return $this->hasMany(ActionEffect::class, 'action_id');
    }

    /**
     * The action's stable handle as a value object.
     */
    public function handle(): EconomySlug
    {
        return EconomySlug::fromString($this->slug);
    }

    /**
     * Determine whether the action belongs to the given profile.
     */
    public function belongsToProfile(BalanceProfile $profile): bool
    {
        return $this->balance_profile_id === $profile->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EconomyActionFactory
    {
        return EconomyActionFactory::new();
    }
}
