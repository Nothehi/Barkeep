<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\BalanceObservationFactory;
use Modules\Identity\Domain\Models\User;

/**
 * What the studio noticed about the economy.
 *
 * "Wood becomes effectively unlimited after round six."
 *
 * Deliberately not a copy of a playtest observation, and the distinction is the
 * reason this table exists at all. Playtesting records what happened at the
 * table — "the green player never bought a building" — and this records what
 * that means for the numbers. One is evidence and the other is the balance
 * interpretation of it, and collapsing them would make it impossible to disagree
 * with an interpretation without editing the evidence.
 *
 * `source_reference` is therefore a plain string rather than a foreign key.
 * Pointing it at a playtest would put another context's identifier in this
 * module's schema and give it a second copy of records it does not own.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $title
 * @property string $observation
 * @property ObservationSourceType $source_type
 * @property string|null $source_reference
 * @property ObservationSeverity $severity
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read User|null $creator
 */
#[Fillable(['title', 'observation', 'source_reference'])]
class BalanceObservation extends Model
{
    /** @use HasFactory<BalanceObservationFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'source_type' => ObservationSourceType::Other->value,
        'severity' => ObservationSeverity::Info->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => ObservationSourceType::class,
            'severity' => ObservationSeverity::class,
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
     * Determine whether this came from a table rather than from a desk.
     */
    public function isEmpirical(): bool
    {
        return $this->source_type->isEmpirical();
    }

    /**
     * Determine whether somebody has to act on this.
     */
    public function demandsAction(): bool
    {
        return $this->severity->demandsAction();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BalanceObservationFactory
    {
        return BalanceObservationFactory::new();
    }
}
