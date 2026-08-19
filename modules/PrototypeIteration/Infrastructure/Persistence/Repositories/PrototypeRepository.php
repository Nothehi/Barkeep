<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\DTOs\PrototypeFilters;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\ValueObjects\PrototypeVersionNumber;

/**
 * Every read the module performs against its prototype tables.
 *
 * Collecting them here is what makes "a prototype is only ever visible through
 * its game" checkable: there is one method that lists prototypes, it takes a
 * game, and no query elsewhere gets the chance to forget the scope. The game
 * itself was resolved through a workspace, so the whole ownership chain —
 * workspace, game, prototype, version, artifact — holds by construction rather
 * than by each caller remembering to check it.
 *
 * Nothing here authorizes. Resolving a record and deciding who may see it are
 * separate steps, and every caller runs a policy on the result; merging the two
 * would make it easy to forget the second half.
 */
final class PrototypeRepository
{
    /**
     * The prototypes of a game, newest first.
     *
     * The game is a parameter rather than a filter, so there is no way to call
     * this without one. Ordering is newest first because the prototype somebody
     * wants is almost always the one they were working on this week, and falls
     * back to the id so the order is total rather than leaving rows written in
     * the same second to the database's whim.
     *
     * @return Collection<int, Prototype>
     */
    public function forGame(Game $game, ?PrototypeFilters $filters = null): Collection
    {
        $filters ??= PrototypeFilters::none();

        $prototypes = Prototype::query()
            ->where('game_id', $game->getKey())
            ->when(
                $filters->status !== null,
                fn (Builder $query) => $query->where('status', $filters->status),
            )
            ->when(
                $filters->type !== null,
                fn (Builder $query) => $query->where('type', $filters->type),
            )
            ->when(
                $filters->search !== null,
                fn (Builder $query) => $this->applySearch($query, (string) $filters->search),
            )
            ->with('version')
            ->withCount('versions')
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $this->withGame($game, $prototypes);
    }

    /**
     * Find one of a game's prototypes by id.
     *
     * Scoped to the game for the same reason the list is: it is how the prototype
     * is identified at all on a nested route, and it means a prototype from
     * another game fails to resolve rather than being caught later by a policy.
     */
    public function findForGame(Game $game, string $prototypeId): ?Prototype
    {
        $prototype = Prototype::query()
            ->where('game_id', $game->getKey())
            ->whereKey($prototypeId)
            ->with(['version', 'creator'])
            ->first();

        return $prototype === null ? null : $prototype->setRelation('game', $game);
    }

    /**
     * A prototype's states, newest first.
     *
     * The reverse of the order GameDesign lists game versions in, and the reverse
     * of how a playtest's sessions read — both deliberate. A prototype's versions
     * are a stack rather than a sequence: what somebody wants is the current
     * build, and v1 is history. Sessions are read forwards because they tell a
     * story; versions are read backwards because the top one is the answer.
     *
     * @return Collection<int, PrototypeVersion>
     */
    public function versionsOf(Prototype $prototype): Collection
    {
        return $prototype->versions()
            ->with('creator')
            ->withCount(['artifacts', 'iterations'])
            ->orderByDesc('version_number')
            ->get();
    }

    /**
     * Find one of a prototype's states by its number.
     *
     * By number rather than by id, because that is how the route addresses it and
     * how a designer says it. The number is only meaningful inside one prototype,
     * which is exactly why the lookup is scoped to one.
     */
    public function findVersionForPrototype(Prototype $prototype, PrototypeVersionNumber $number): ?PrototypeVersion
    {
        $version = $prototype->versions()
            ->where('version_number', $number->value)
            ->with('creator')
            ->first();

        return $version === null ? null : $version->setRelation('prototype', $prototype);
    }

    /**
     * Find one of a game's prototype states by id, across all its prototypes.
     *
     * The lookup behind the module's central invariant. A prototype version id
     * arrives in a request body when an iteration is planned, and resolving it
     * *through* the game — rather than by id and then comparing — is what makes a
     * version from another studio's project simply not resolve.
     *
     * The join is on `prototypes` rather than a `whereHas`, because the caller
     * almost always wants the prototype too: an iteration form shows "Core Combat
     * Prototype v4", not "v4".
     */
    public function findVersionOfGame(Game $game, string $prototypeVersionId): ?PrototypeVersion
    {
        return PrototypeVersion::query()
            ->whereKey($prototypeVersionId)
            ->whereHas('prototype', fn (Builder $query) => $query->where('game_id', $game->getKey()))
            ->with('prototype')
            ->first();
    }

    /**
     * Determine whether a prototype version id names one of this game's states.
     *
     * Used by validation, which wants to report the problem next to the field
     * rather than to raise it.
     */
    public function gameHasVersion(Game $game, string $prototypeVersionId): bool
    {
        return PrototypeVersion::query()
            ->whereKey($prototypeVersionId)
            ->whereHas('prototype', fn (Builder $query) => $query->where('game_id', $game->getKey()))
            ->exists();
    }

    /**
     * Every prototype state in a game, newest prototype and version first.
     *
     * What the "which build was this?" picker on an iteration form reads. Loaded
     * with the prototype because a bare "v4" is meaningless when a game has three
     * prototypes.
     *
     * @return Collection<int, PrototypeVersion>
     */
    public function selectableVersionsOf(Game $game): Collection
    {
        return PrototypeVersion::query()
            ->whereHas('prototype', fn (Builder $query) => $query->where('game_id', $game->getKey()))
            ->with('prototype')
            ->orderByDesc('created_at')
            ->orderByDesc('version_number')
            ->get();
    }

    /**
     * The number that follows a prototype's highest existing state.
     *
     * Read inside the transaction that allocates it — see
     * `CreatePrototypeVersion`, which locks the prototype first. On its own this
     * is a race; the lock and the unique index are what make it safe.
     */
    public function nextVersionNumberFor(Prototype $prototype): PrototypeVersionNumber
    {
        $highest = $prototype->versions()->max('version_number');

        return $highest === null
            ? PrototypeVersionNumber::first()
            : PrototypeVersionNumber::fromInt((int) $highest)->next();
    }

    /**
     * How many times a prototype state has been built upon.
     *
     * The immutability check, and it counts iterations rather than asking whether
     * any exist so that the refusal can say how much history is at stake. An
     * iteration pointing at a version has recorded what that version was, so a
     * version with any iterations at all is part of the design record.
     */
    public function countIterationsOfVersion(PrototypeVersion $version): int
    {
        return Iteration::query()
            ->where('prototype_version_id', $version->getKey())
            ->count();
    }

    /**
     * Determine whether a prototype state has been used and is therefore frozen.
     */
    public function versionIsInUse(PrototypeVersion $version): bool
    {
        return $this->countIterationsOfVersion($version) > 0;
    }

    /**
     * The files attached to a prototype state, in upload order.
     *
     * Forwards rather than backwards, unlike the versions above. Artifacts are a
     * set rather than a stack — somebody uploads the card fronts, the backs and
     * then the rulebook, and reading them back in that order is how they think of
     * the group.
     *
     * @return Collection<int, PrototypeArtifact>
     */
    public function artifactsOf(PrototypeVersion $version): Collection
    {
        return $version->artifacts()
            ->with('creator')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one of a prototype state's artifacts by id.
     *
     * Scoped to the version, so an artifact id from somebody else's prototype
     * fails to resolve rather than being caught later by a policy. This is the
     * lookup that stands between an opaque id in a download URL and a studio's
     * unreleased card art.
     */
    public function findArtifactInVersion(PrototypeVersion $version, string $artifactId): ?PrototypeArtifact
    {
        $artifact = $version->artifacts()->whereKey($artifactId)->with('creator')->first();

        return $artifact === null ? null : $artifact->setRelation('prototypeVersion', $version);
    }

    /**
     * How many states a prototype has.
     */
    public function countVersionsOf(Prototype $prototype): int
    {
        return $prototype->versions()->count();
    }

    /**
     * Determine whether a prototype has any states at all.
     */
    public function hasVersions(Prototype $prototype): bool
    {
        return $prototype->versions()->exists();
    }

    /**
     * Narrow a prototype query by what somebody typed.
     *
     * Name and description, case folded on both sides so that searching "hex"
     * finds "Hex tile draft" on every database the application runs on rather
     * than only on the ones whose collation happens to be insensitive. Both
     * columns, because a designer searching their own prototypes is as likely to
     * remember "the one with the hex tiles" as the name they gave it.
     *
     * @param  Builder<Prototype>  $query
     * @return Builder<Prototype>
     */
    private function applySearch(Builder $query, string $term): Builder
    {
        $pattern = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term)).'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->whereRaw('LOWER(name) LIKE ? ESCAPE ?', [$pattern, '\\'])
                ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ? ESCAPE ?", [$pattern, '\\']);
        });
    }

    /**
     * Hand every prototype the game it was found through.
     *
     * Saves a lazy load per row on the way out, and — more importantly — means
     * the permission answers computed for a list all read the same game instance,
     * with its one memoised workspace membership, rather than each reloading it.
     *
     * @param  Collection<int, Prototype>  $prototypes
     * @return Collection<int, Prototype>
     */
    private function withGame(Game $game, Collection $prototypes): Collection
    {
        return $prototypes->each(fn (Prototype $prototype) => $prototype->setRelation('game', $game));
    }
}
