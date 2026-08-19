<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;

/**
 * Every read the module performs against a profile and the records filed
 * alongside it.
 *
 * Collecting them here is what makes "a balance profile is only ever visible
 * through its game version" checkable: there is one method that lists profiles,
 * it takes a version, and no query elsewhere gets the chance to forget the
 * scope. The version was itself resolved through a game, and the game through a
 * workspace, so the whole ownership chain —
 *
 *     workspace → game → version → profile → assumption | observation | snapshot
 *
 * — holds by construction rather than by each caller remembering it.
 *
 * Nothing here authorizes. Resolving a record and deciding who may see it are
 * separate steps, and every caller runs a policy on the result.
 */
final class BalanceProfileRepository
{
    /**
     * The balance configurations of a design state, newest first.
     *
     * The version is a parameter rather than a filter, so there is no way to
     * call this without one. Ordering falls back to the id so it is total rather
     * than leaving rows written in the same second to the database's whim.
     *
     * @return Collection<int, BalanceProfile>
     */
    public function forVersion(GameVersion $version): Collection
    {
        $profiles = BalanceProfile::query()
            ->where('game_version_id', $version->getKey())
            ->with('creator')
            ->withCount(['resources', 'actions', 'variables', 'scenarios', 'snapshots'])
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $profiles->each(fn (BalanceProfile $profile) => $profile->setRelation('version', $version));
    }

    /**
     * Find one of a design state's configurations by id.
     *
     * Scoped to the version for the same reason the list is: a profile from
     * another version fails to resolve rather than being caught later by a
     * policy.
     */
    public function findForVersion(GameVersion $version, string $profileId): ?BalanceProfile
    {
        $profile = BalanceProfile::query()
            ->where('game_version_id', $version->getKey())
            ->whereKey($profileId)
            ->with('creator')
            ->first();

        return $profile === null ? null : $profile->setRelation('version', $version);
    }

    /**
     * The configuration currently in play for a design state, if there is one.
     *
     * At most one row can match: the partial unique index on the table makes
     * that a fact about the data rather than an assumption about the query.
     */
    public function activeForVersion(GameVersion $version): ?BalanceProfile
    {
        $profile = BalanceProfile::query()
            ->where('game_version_id', $version->getKey())
            ->where('status', BalanceProfileStatus::Active)
            ->with('creator')
            ->first();

        return $profile === null ? null : $profile->setRelation('version', $version);
    }

    /**
     * Every configuration in a game, across all its design states.
     *
     * What the game-level balance overview reads. Loaded with the version
     * because a bare "Tuning pass" is meaningless when a game has six versions.
     *
     * @return Collection<int, BalanceProfile>
     */
    public function forGame(Game $game): Collection
    {
        return BalanceProfile::query()
            ->whereHas('version', fn (Builder $query) => $query->where('game_id', $game->getKey()))
            ->with(['version', 'creator'])
            ->withCount(['resources', 'actions', 'variables'])
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Determine whether a design state already carries a profile by this name.
     *
     * Used by validation, which wants to report the clash next to the field
     * rather than let the unique index raise.
     */
    public function versionHasProfileNamed(GameVersion $version, string $name, ?string $ignoreId = null): bool
    {
        return BalanceProfile::query()
            ->where('game_version_id', $version->getKey())
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * The beliefs a configuration's numbers were chosen to satisfy.
     *
     * Ordered by how strongly they are held and then newest first, so the things
     * the studio is least sure about — the ones worth going and testing — are
     * not buried under the settled ones.
     *
     * @return Collection<int, BalanceAssumption>
     */
    public function assumptionsOf(BalanceProfile $profile): Collection
    {
        $assumptions = $profile->assumptions()
            ->with('creator')
            ->orderBy('confidence')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $assumptions->each(fn (BalanceAssumption $assumption) => $assumption->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's assumptions by id.
     */
    public function findAssumptionInProfile(BalanceProfile $profile, string $assumptionId): ?BalanceAssumption
    {
        $assumption = $profile->assumptions()->whereKey($assumptionId)->with('creator')->first();

        return $assumption === null ? null : $assumption->setRelation('profile', $profile);
    }

    /**
     * What the studio noticed about the economy, worst first.
     *
     * The opposite of the assumptions ordering above, and deliberately so. An
     * assumption list is read to find what has not been checked; an observation
     * list is read to find what is on fire.
     *
     * @return Collection<int, BalanceObservation>
     */
    public function observationsOf(BalanceProfile $profile): Collection
    {
        $observations = $profile->observations()
            ->with('creator')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $observations
            ->sortByDesc(fn (BalanceObservation $observation): int => $observation->severity->weight())
            ->values()
            ->each(fn (BalanceObservation $observation) => $observation->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's observations by id.
     */
    public function findObservationInProfile(BalanceProfile $profile, string $observationId): ?BalanceObservation
    {
        $observation = $profile->observations()->whereKey($observationId)->with('creator')->first();

        return $observation === null ? null : $observation->setRelation('profile', $profile);
    }

    /**
     * A configuration's frozen states, newest first.
     *
     * @return Collection<int, BalanceSnapshot>
     */
    public function snapshotsOf(BalanceProfile $profile): Collection
    {
        $snapshots = $profile->snapshots()
            ->with('creator')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $snapshots->each(fn (BalanceSnapshot $snapshot) => $snapshot->setRelation('profile', $profile));
    }

    /**
     * Find one of a configuration's snapshots by id.
     */
    public function findSnapshotInProfile(BalanceProfile $profile, string $snapshotId): ?BalanceSnapshot
    {
        $snapshot = $profile->snapshots()->whereKey($snapshotId)->with('creator')->first();

        return $snapshot === null ? null : $snapshot->setRelation('profile', $profile);
    }
}
