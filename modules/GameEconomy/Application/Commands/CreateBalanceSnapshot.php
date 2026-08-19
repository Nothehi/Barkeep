<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceSnapshotData;
use Modules\GameEconomy\Domain\Events\BalanceSnapshotCreated;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Infrastructure\Calculations\SnapshotWriter;
use Modules\Identity\Domain\Models\User;

/**
 * Freeze a configuration as it stands.
 *
 * The payload is read from the live tables rather than supplied, because a
 * snapshot whose contents a caller could choose would not be a record of
 * anything. The only inputs are a name and a description — labels for a thing
 * the module produces.
 *
 * Deliberately *not* guarded on the profile still being open. An archived
 * profile is exactly the thing somebody wants to snapshot — "keep a copy of what
 * we shipped" is a reason to take one, not a reason to refuse — and taking one
 * changes nothing about the live configuration, so there is nothing for the
 * archived status to protect.
 *
 * What is written here can never be rewritten. The model refuses updates at the
 * Eloquent level and the table has no `updated_at`, so the immutability rule
 * holds against a console script and a future module as well as against this
 * application's own routes.
 */
final class CreateBalanceSnapshot
{
    public function __construct(private readonly SnapshotWriter $writer) {}

    public function handle(User $creator, BalanceProfile $profile, BalanceSnapshotData $data): BalanceSnapshot
    {
        $snapshot = new BalanceSnapshot;

        $snapshot->balance_profile_id = $profile->getKey();
        $snapshot->name = $data->name;
        $snapshot->description = $data->description;
        $snapshot->snapshot_data = $this->writer->capture($profile);
        $snapshot->created_by = $creator->id;

        $snapshot->save();

        $snapshot->setRelation('profile', $profile);
        $snapshot->setRelation('creator', $creator);

        event(new BalanceSnapshotCreated(
            snapshotId: $snapshot->id,
            profileId: $profile->getKey(),
            tally: $snapshot->tally(),
            createdBy: $creator->id,
        ));

        return $snapshot;
    }
}
