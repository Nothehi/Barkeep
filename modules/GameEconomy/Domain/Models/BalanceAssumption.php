<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\BalanceAssumptionFactory;
use Modules\Identity\Domain\Models\User;

/**
 * Why a number is what it is.
 *
 * "Players should generate enough food to perform one major action every round."
 * A profile without these is a spreadsheet: it says wood production is 3 and
 * gives the next designer no way to find out whether that was measured, argued
 * for or typed.
 *
 * The confidence is the field that makes the record honest. The same sentence
 * held with low confidence is a thing to go and test; held with high confidence
 * it is a thing to design around. A table that could not tell them apart would
 * flatten every hunch into an assertion and make the whole economy read as more
 * settled than it is.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $title
 * @property string|null $description
 * @property AssumptionCategory $category
 * @property AssumptionConfidence $confidence
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read BalanceProfile|null $profile
 * @property-read User|null $creator
 */
#[Fillable(['title', 'description'])]
class BalanceAssumption extends Model
{
    /** @use HasFactory<BalanceAssumptionFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => AssumptionCategory::Other->value,
        'confidence' => AssumptionConfidence::Medium->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AssumptionCategory::class,
            'confidence' => AssumptionConfidence::class,
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
     * Determine whether this is a belief somebody should go and test.
     */
    public function needsEvidence(): bool
    {
        return $this->confidence->needsEvidence();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BalanceAssumptionFactory
    {
        return BalanceAssumptionFactory::new();
    }
}
