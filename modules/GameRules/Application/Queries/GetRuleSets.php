<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\DTOs\RuleSetFilters;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleSetRepository;

/**
 * The rule sets of a design state, newest first.
 *
 * Filtering happens in memory rather than in the query, which is the right trade
 * at this size: a design version carries a handful of rule sets — a draft, the one
 * in play, and the archived ones before it — and pushing a `LIKE` into the
 * database to narrow four rows would buy nothing and give the scoping a second
 * place to live.
 */
final class GetRuleSets
{
    public function __construct(private readonly RuleSetRepository $ruleSets) {}

    /**
     * @return Collection<int, RuleSet>
     */
    public function handle(GameVersion $version, ?RuleSetFilters $filters = null): Collection
    {
        $ruleSets = $this->ruleSets->forVersion($version);

        if ($filters === null || $filters->isEmpty()) {
            return $ruleSets;
        }

        return $ruleSets
            ->when(
                $filters->status !== null,
                fn (Collection $all): Collection => $all->filter(
                    fn (RuleSet $ruleSet): bool => $ruleSet->status === $filters->status,
                ),
            )
            ->when(
                $filters->search !== null,
                fn (Collection $all): Collection => $all->filter(
                    fn (RuleSet $ruleSet): bool => str_contains(
                        mb_strtolower($ruleSet->name.' '.($ruleSet->description ?? '')),
                        mb_strtolower((string) $filters->search),
                    ),
                ),
            )
            ->values();
    }
}
