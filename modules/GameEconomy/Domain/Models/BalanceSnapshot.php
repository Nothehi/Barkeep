<?php

namespace Modules\GameEconomy\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories\BalanceSnapshotFactory;
use Modules\Identity\Domain\Models\User;

/**
 * A balance configuration, frozen.
 *
 * "What did the economy look like when we ran the convention playtest?" cannot
 * be answered from the live tables — they have moved on, which is why somebody
 * is asking. A snapshot is the answer, and it has to survive every subsequent
 * edit, including edits that delete the rows it describes.
 *
 * So the payload is a copy rather than a set of references, and the model is
 * immutable: there is no `updated_at`, no fillable attribute, no update command
 * and no route that changes one. That is enforced structurally rather than by
 * convention — {@see performUpdate()} refuses, so even a stray `save()` on a
 * loaded snapshot cannot rewrite history.
 *
 * JSON here and normalised rows everywhere else is not an inconsistency. The
 * live configuration is queried, filtered and summed; a snapshot is only ever
 * read whole and diffed against another, and giving it thirteen shadow tables
 * would mean every future schema change had to be applied to history as well as
 * to the present — which is exactly what "history is immutable" forbids.
 *
 * @property string $id
 * @property string $balance_profile_id
 * @property string $name
 * @property string|null $description
 * @property array<string, mixed> $snapshot_data
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property-read BalanceProfile|null $profile
 * @property-read User|null $creator
 */
class BalanceSnapshot extends Model
{
    /** @use HasFactory<BalanceSnapshotFactory> */
    use HasFactory, HasUuids;

    /**
     * Snapshots record when they were taken and are never touched again, so
     * there is no `updated_at` column for Eloquent to maintain.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
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
     * Refuse to rewrite a snapshot.
     *
     * The immutability rule, enforced at the lowest point it can be. A guard in
     * a command protects the paths that go through that command; this protects
     * every path there is, including a console script and a future module that
     * has not been written yet.
     *
     * Reported as "nothing was updated" rather than by raising, because that is
     * exactly what happened and it is what Eloquent's own contract says a false
     * return means.
     *
     * @param  Builder<static>  $query
     * @param  array<string, mixed>  $attributes
     */
    protected function performUpdate($query, array $attributes = []): bool
    {
        return false;
    }

    /**
     * How many of each kind of record the snapshot holds.
     *
     * Read from the payload rather than counted against the live tables, which
     * is the point: a snapshot's figures describe the configuration as it was,
     * and would be wrong the moment they were computed from the present.
     *
     * @return array<string, int>
     */
    public function tally(): array
    {
        $data = $this->snapshot_data;

        return [
            'resources' => count($data['resources'] ?? []),
            'flows' => count($data['flows'] ?? []),
            'actions' => count($data['actions'] ?? []),
            'variables' => count($data['variables'] ?? []),
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BalanceSnapshotFactory
    {
        return BalanceSnapshotFactory::new();
    }
}
