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
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\BalanceScenarioFactory;
use Modules\Identity\Domain\Models\User;

/**
 * A hypothetical situation the economy is read under.
 *
 * "Two player." "Rich economy." "Late game." A scenario names a situation and
 * then states the variables that differ in it, leaving every other number as the
 * profile says it is.
 *
 * The overrides live in their own table and never touch the base variable, which
 * is section 20 of the brief made structural rather than remembered: there is no
 * code path on which applying a scenario writes to `balance_variables`, because
 * a scenario's values are not stored there.
 *
 * Any number of scenarios may be active at once, unlike profiles. A studio
 * comparing two-player against four-player needs both live, and that is the
 * whole point of having them.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $name
 * @property string|null $description
 * @property BalanceScenarioStatus $status
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read User|null $creator
 * @property-read Collection<int, ScenarioVariable> $overrides
 * @property-read int|null $overrides_count
 */
#[Fillable(['name', 'description'])]
class BalanceScenario extends Model
{
    /** @use HasFactory<BalanceScenarioFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => BalanceScenarioStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BalanceScenarioStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The values this scenario states differently.
     *
     * @return HasMany<ScenarioVariable, $this>
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(ScenarioVariable::class, 'scenario_id');
    }

    /**
     * Determine whether the scenario and its overrides may still be changed.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the scenario has been put away.
     */
    public function isArchived(): bool
    {
        return $this->status === BalanceScenarioStatus::Archived;
    }

    /**
     * Determine whether the scenario belongs to the given profile.
     */
    public function belongsToProfile(BalanceProfile $profile): bool
    {
        return $this->balance_profile_id === $profile->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BalanceScenarioFactory
    {
        return BalanceScenarioFactory::new();
    }
}
