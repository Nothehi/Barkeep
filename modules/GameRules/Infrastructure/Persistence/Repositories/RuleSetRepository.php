<?php

namespace Modules\GameRules\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * Every read the module performs against a rule set itself.
 *
 * Collecting them here is what makes "a rule set is only ever visible through its
 * game version" checkable: there is one method that lists them, it takes a
 * version, and no query elsewhere gets the chance to forget the scope. The
 * version was itself resolved through a game, and the game through a workspace,
 * so the whole ownership chain —
 *
 *     workspace → game → version → rule set
 *
 * — holds by construction rather than by each caller remembering it.
 *
 * Nothing here authorizes. Resolving a record and deciding who may see it are
 * separate steps, and every caller runs a policy on the result.
 */
final class RuleSetRepository
{
    /**
     * The rule sets of a design state, newest first.
     *
     * The version is a parameter rather than a filter, so there is no way to call
     * this without one. Ordering falls back to the id so it is total rather than
     * leaving rows written in the same second to the database's whim.
     *
     * @return Collection<int, RuleSet>
     */
    public function forVersion(GameVersion $version): Collection
    {
        $ruleSets = RuleSet::query()
            ->where('game_version_id', $version->getKey())
            ->with('creator')
            ->withCount(['rules', 'mechanics', 'phases', 'actions', 'conditions'])
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $ruleSets->each(fn (RuleSet $ruleSet) => $ruleSet->setRelation('version', $version));
    }

    /**
     * Find one of a design state's rule sets by id.
     *
     * Scoped to the version for the same reason the list is: a set from another
     * version fails to resolve rather than being caught later by a policy. That
     * is what lets a rule set id be an opaque uuid in a URL without being a
     * capability.
     */
    public function findForVersion(GameVersion $version, string $ruleSetId): ?RuleSet
    {
        $ruleSet = RuleSet::query()
            ->where('game_version_id', $version->getKey())
            ->whereKey($ruleSetId)
            ->with('creator')
            ->first();

        return $ruleSet === null ? null : $ruleSet->setRelation('version', $version);
    }

    /**
     * The rule system currently in play for a design state, if there is one.
     *
     * At most one row can match: the partial unique index on the table makes that
     * a fact about the data rather than an assumption about the query.
     */
    public function activeForVersion(GameVersion $version): ?RuleSet
    {
        $ruleSet = RuleSet::query()
            ->where('game_version_id', $version->getKey())
            ->where('status', RuleSetStatus::Active)
            ->with('creator')
            ->first();

        return $ruleSet === null ? null : $ruleSet->setRelation('version', $version);
    }

    /**
     * Every rule set in a game, across all its design states.
     *
     * What a game-level overview reads. Loaded with the version because a bare
     * "First draft" is meaningless when a game has six versions.
     *
     * @return Collection<int, RuleSet>
     */
    public function forGame(Game $game): Collection
    {
        return RuleSet::query()
            ->whereHas('version', fn (Builder $query) => $query->where('game_id', $game->getKey()))
            ->with(['version', 'creator'])
            ->withCount(['rules', 'phases', 'actions'])
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Determine whether a design state already carries a rule set by this name.
     *
     * Used by validation, which wants to report the clash next to the field
     * rather than let the unique index raise.
     */
    public function versionHasRuleSetNamed(GameVersion $version, string $name, ?string $ignoreId = null): bool
    {
        return RuleSet::query()
            ->where('game_version_id', $version->getKey())
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * A name for a clone that the design state does not already use.
     *
     * Cloning is the module's answer to "I want to change the rules in play", so
     * it has to succeed without making somebody invent a name first. "Combat
     * rules (copy)", then "(copy 2)", and so on.
     */
    public function availableCloneName(GameVersion $version, string $sourceName): string
    {
        $base = mb_substr(__(':name (copy)', ['name' => $sourceName]), 0, 160);

        if (! $this->versionHasRuleSetNamed($version, $base)) {
            return $base;
        }

        for ($suffix = 2; $suffix < 100; $suffix++) {
            $candidate = mb_substr(__(':name (copy :number)', [
                'name' => $sourceName,
                'number' => $suffix,
            ]), 0, 160);

            if (! $this->versionHasRuleSetNamed($version, $candidate)) {
                return $candidate;
            }
        }

        return mb_substr($sourceName.' '.uniqid(), 0, 160);
    }
}
