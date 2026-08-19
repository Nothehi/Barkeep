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
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\BalanceProfileFactory;
use Modules\Identity\Domain\Models\User;

/**
 * The complete quantitative configuration of a game, at one point in its design.
 *
 * A profile is the aggregate everything else in this module hangs off. Resources,
 * flows, actions, costs, rewards, effects, variables, scenarios, assumptions and
 * observations all belong to one, which is what makes "freeze the economy" and
 * "compare two economies" single operations rather than thirteen.
 *
 * It belongs to a `GameVersion` and never to a `Game`, and that is the module's
 * foundational decision rather than a schema detail. Wood income was 2 in v1 and
 * 3 in v2; if the numbers hung off the game, the second answer would overwrite
 * the first and every playtest run against v1 would become uninterpretable. A
 * newer profile is never applied backwards to an older version — see section 48
 * of the module brief.
 *
 * A version may carry several profiles: drafts being tuned, the one in play, and
 * the archived ones that came before. Exactly one may be active, which the
 * database enforces with a partial unique index rather than this class enforcing
 * it in PHP.
 *
 * @property string $id
 * @property string $game_version_id
 * @property string $name
 * @property string|null $description
 * @property BalanceProfileStatus $status
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameVersion|null $version
 * @property-read User|null $creator
 * @property-read Collection<int, ResourceType> $resources
 * @property-read Collection<int, ResourceFlow> $flows
 * @property-read Collection<int, EconomyAction> $actions
 * @property-read Collection<int, BalanceVariable> $variables
 * @property-read Collection<int, BalanceScenario> $scenarios
 * @property-read Collection<int, BalanceAssumption> $assumptions
 * @property-read Collection<int, BalanceObservation> $observations
 * @property-read Collection<int, BalanceSnapshot> $snapshots
 * @property-read int|null $resources_count
 * @property-read int|null $flows_count
 * @property-read int|null $actions_count
 * @property-read int|null $variables_count
 * @property-read int|null $scenarios_count
 * @property-read int|null $assumptions_count
 * @property-read int|null $observations_count
 * @property-read int|null $snapshots_count
 */
#[Fillable(['name', 'description'])]
class BalanceProfile extends Model
{
    /** @use HasFactory<BalanceProfileFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => BalanceProfileStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BalanceProfileStatus::class,
        ];
    }

    /**
     * The design state this profile configures.
     *
     * @return BelongsTo<GameVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }

    /**
     * The account that started the configuration.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ResourceType, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ResourceType::class);
    }

    /**
     * @return HasMany<ResourceFlow, $this>
     */
    public function flows(): HasMany
    {
        return $this->hasMany(ResourceFlow::class);
    }

    /**
     * @return HasMany<EconomyAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(EconomyAction::class);
    }

    /**
     * @return HasMany<BalanceVariable, $this>
     */
    public function variables(): HasMany
    {
        return $this->hasMany(BalanceVariable::class);
    }

    /**
     * @return HasMany<BalanceScenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(BalanceScenario::class);
    }

    /**
     * @return HasMany<BalanceAssumption, $this>
     */
    public function assumptions(): HasMany
    {
        return $this->hasMany(BalanceAssumption::class);
    }

    /**
     * @return HasMany<BalanceObservation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(BalanceObservation::class);
    }

    /**
     * @return HasMany<BalanceSnapshot, $this>
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(BalanceSnapshot::class);
    }

    /**
     * Determine whether the configuration may still be changed.
     *
     * Only answers for the profile. Whether the game around it is still
     * accepting changes is a separate question, and both have to be true — see
     * the policy and the guard, which check each in turn.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether this is the configuration currently in play.
     */
    public function isActive(): bool
    {
        return $this->status === BalanceProfileStatus::Active;
    }

    /**
     * Determine whether the profile has been put away.
     */
    public function isArchived(): bool
    {
        return $this->status === BalanceProfileStatus::Archived;
    }

    /**
     * Determine whether the profile configures the given design state.
     *
     * Used where a version has been resolved separately from the profile, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToVersion(GameVersion $version): bool
    {
        return $this->game_version_id === $version->getKey();
    }

    /**
     * Determine whether the profile ultimately belongs to the given game.
     *
     * Relies on the version relation, so callers that have not loaded it get a
     * lazy read rather than a wrong answer.
     */
    public function belongsToGame(Game $game): bool
    {
        return $this->version?->game_id === $game->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BalanceProfileFactory
    {
        return BalanceProfileFactory::new();
    }
}
