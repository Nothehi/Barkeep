<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\DTOs\IterationFilters;
use Modules\PrototypeIteration\Application\DTOs\IterationSummary;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Every read the module performs against its iteration tables.
 *
 * The same arrangement as `PrototypeRepository` and for the same reason: there is
 * one method that lists iterations, it takes a game, and no query elsewhere gets
 * the chance to forget the scope. The game was resolved through a workspace, so
 * the ownership chain — workspace, game, iteration, change or decision — holds by
 * construction rather than by each caller remembering it.
 *
 * Nothing here authorizes, and nothing here reaches into Playtesting. The
 * playtest links are read as join rows; turning them into something an interface
 * can render is `PlaytestEvidence`'s job, and keeping that seam outside this
 * class is what stops a convenient `with('playtest')` from appearing in a query
 * six months from now.
 */
final class IterationRepository
{
    /**
     * The iterations of a game, newest first.
     *
     * Newest first because the cycle somebody wants is the one they are in, and
     * because a design history is read backwards from the present when you are
     * working and forwards from the start when you are learning — the second is
     * what the timeline is for.
     *
     * @return Collection<int, Iteration>
     */
    public function forGame(Game $game, ?IterationFilters $filters = null): Collection
    {
        $filters ??= IterationFilters::none();

        $iterations = Iteration::query()
            ->where('game_id', $game->getKey())
            ->when(
                $filters->status !== null,
                fn (Builder $query) => $query->where('status', $filters->status),
            )
            ->when(
                $filters->outcome !== null,
                fn (Builder $query) => $query->where('outcome', $filters->outcome),
            )
            ->when(
                $filters->prototypeId !== null,
                fn (Builder $query) => $query->whereHas(
                    'prototypeVersion',
                    fn (Builder $version) => $version->where('prototype_id', $filters->prototypeId),
                ),
            )
            ->when(
                $filters->search !== null,
                fn (Builder $query) => $this->applySearch($query, (string) $filters->search),
            )
            ->with(['version', 'prototypeVersion.prototype'])
            ->withCount(['changes', 'experiments', 'decisions', 'playtestLinks'])
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->get();

        return $this->withGame($game, $iterations);
    }

    /**
     * How many design cycles have been run across a workspace's games.
     *
     * Scoped through the game for the same reason as everything else here: an
     * iteration knows its game, and the game knows which studio it belongs to.
     * The only caller is the app's home screen, which is about a studio rather
     * than about one design.
     */
    public function countForWorkspace(Workspace $workspace): int
    {
        return $this->inWorkspace($workspace)->count();
    }

    /**
     * How many of a workspace's cycles are still open.
     *
     * Planned and in progress counted together rather than in progress alone,
     * because both are work somebody is expected to come back to — and a studio
     * with nine planned cycles and none started has a queue, which is exactly
     * the thing a home screen should be able to say out loud.
     */
    public function openCountForWorkspace(Workspace $workspace): int
    {
        return $this->inWorkspace($workspace)
            ->whereIn('status', [IterationStatus::Planned, IterationStatus::InProgress])
            ->count();
    }

    /**
     * Every iteration belonging to a workspace, as a query still to be refined.
     *
     * One place says what "in this workspace" means, so the two methods above
     * cannot drift onto different definitions of it.
     *
     * @return Builder<Iteration>
     */
    private function inWorkspace(Workspace $workspace): Builder
    {
        return Iteration::query()->whereHas(
            'game',
            fn (Builder $query) => $query->where('workspace_id', $workspace->getKey()),
        );
    }

    /**
     * Find one of a game's iterations by id.
     */
    public function findForGame(Game $game, string $iterationId): ?Iteration
    {
        $iteration = Iteration::query()
            ->where('game_id', $game->getKey())
            ->whereKey($iterationId)
            ->with(['version', 'prototypeVersion.prototype', 'creator'])
            ->first();

        return $iteration === null ? null : $iteration->setRelation('game', $game);
    }

    /**
     * What a cycle changed, in the order it was recorded.
     *
     * Forwards, because changes are read as an account of what was done rather
     * than as a stack of the most recent.
     *
     * @return Collection<int, DesignChange>
     */
    public function changesOf(Iteration $iteration): Collection
    {
        return $iteration->changes()
            ->with('creator')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one of a cycle's changes by id.
     */
    public function findChangeInIteration(Iteration $iteration, string $changeId): ?DesignChange
    {
        $change = $iteration->changes()->whereKey($changeId)->with('creator')->first();

        return $change === null ? null : $change->setRelation('iteration', $iteration);
    }

    /**
     * What a cycle tried out, in the order it was designed.
     *
     * @return Collection<int, DesignExperiment>
     */
    public function experimentsOf(Iteration $iteration): Collection
    {
        return $iteration->experiments()
            ->with('creator')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one of a cycle's experiments by id.
     */
    public function findExperimentInIteration(Iteration $iteration, string $experimentId): ?DesignExperiment
    {
        $experiment = $iteration->experiments()->whereKey($experimentId)->with('creator')->first();

        return $experiment === null ? null : $experiment->setRelation('iteration', $iteration);
    }

    /**
     * What a cycle concluded, in the order it was proposed.
     *
     * Loaded with its citations, because a decision is read together with what
     * supports it — a decision list that made somebody click through to find out
     * whether anything backed each one would not get read.
     *
     * @return Collection<int, DesignDecision>
     */
    public function decisionsOf(Iteration $iteration): Collection
    {
        return $iteration->decisions()
            ->with(['creator', 'decider', 'evidence.creator'])
            ->withCount('evidence')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one of a cycle's decisions by id.
     */
    public function findDecisionInIteration(Iteration $iteration, string $decisionId): ?DesignDecision
    {
        $decision = $iteration->decisions()
            ->whereKey($decisionId)
            ->with(['creator', 'decider', 'evidence.creator'])
            ->withCount('evidence')
            ->first();

        return $decision === null ? null : $decision->setRelation('iteration', $iteration);
    }

    /**
     * What was cited in support of a decision, in the order it was cited.
     *
     * @return Collection<int, DecisionEvidence>
     */
    public function evidenceOf(DesignDecision $decision): Collection
    {
        return $decision->evidence()
            ->with('creator')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one of a decision's citations by id.
     */
    public function findEvidenceInDecision(DesignDecision $decision, string $evidenceId): ?DecisionEvidence
    {
        $evidence = $decision->evidence()->whereKey($evidenceId)->with('creator')->first();

        return $evidence === null ? null : $evidence->setRelation('decision', $decision);
    }

    /**
     * The playtests a cycle was tested through, as join rows.
     *
     * Join rows and nothing more — see the note at the top of this class. Ordered
     * by when they were attached, which is usually the order the playtests
     * happened in and is always the order the studio connected them in.
     *
     * @return Collection<int, IterationPlaytest>
     */
    public function playtestLinksOf(Iteration $iteration): Collection
    {
        return $iteration->playtestLinks()
            ->with('creator')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one of a cycle's playtest links by id.
     *
     * By link id rather than by playtest id, because detaching addresses the
     * association rather than the playtest — which is what lets the detach route
     * name nothing belonging to Playtesting at all.
     */
    public function findPlaytestLinkInIteration(Iteration $iteration, string $linkId): ?IterationPlaytest
    {
        $link = $iteration->playtestLinks()->whereKey($linkId)->first();

        return $link === null ? null : $link->setRelation('iteration', $iteration);
    }

    /**
     * Determine whether a playtest is already attached to a cycle.
     *
     * Checked before writing so the caller gets a worded refusal rather than a
     * constraint violation. The unique index is still the authority: a
     * double-submitted form is stopped by the database whether or not this ran.
     */
    public function hasPlaytest(Iteration $iteration, string $playtestId): bool
    {
        return $iteration->playtestLinks()->where('playtest_id', $playtestId)->exists();
    }

    /**
     * Count everything a cycle produced from its own tables.
     *
     * The playtesting figures are absent on purpose — they belong to Playtesting
     * and are added by `GetIterationSummary` through this module's adapter, which
     * is what keeps them equal to the numbers on the playtest's own screen. This
     * method knows the count of *links*, which is a fact about this module's
     * tables, and nothing about what those playtests contain.
     *
     * @return array{changes: int, experiments: int, completed_experiments: int, decisions: int, accepted_decisions: int, evidence: int, playtests: int}
     */
    public function tally(Iteration $iteration): array
    {
        $decisionIds = $iteration->decisions()->pluck('id');

        return [
            'changes' => $iteration->changes()->count(),
            'experiments' => $iteration->experiments()->count(),
            'completed_experiments' => $iteration->experiments()
                ->where('status', ExperimentStatus::Completed)
                ->count(),
            'decisions' => $decisionIds->count(),
            'accepted_decisions' => $iteration->decisions()
                ->where('status', DecisionStatus::Accepted)
                ->count(),
            'evidence' => $decisionIds->isEmpty()
                ? 0
                : DecisionEvidence::query()->whereIn('decision_id', $decisionIds)->count(),
            'playtests' => $iteration->playtestLinks()->count(),
        ];
    }

    /**
     * Build the summary of a cycle from its own tables alone.
     *
     * The playtesting figures come back as zero here. `GetIterationSummary`
     * replaces them with what Playtesting reports, and the split exists so that
     * this class stays ignorant of that module — a repository that reached across
     * the seam would be the first place the boundary quietly stopped holding.
     */
    public function summarise(Iteration $iteration): IterationSummary
    {
        $tally = $this->tally($iteration);

        return new IterationSummary(
            iteration: $iteration,
            changeCount: $tally['changes'],
            experimentCount: $tally['experiments'],
            completedExperimentCount: $tally['completed_experiments'],
            decisionCount: $tally['decisions'],
            acceptedDecisionCount: $tally['accepted_decisions'],
            evidenceCount: $tally['evidence'],
            playtestCount: $tally['playtests'],
            sessionCount: 0,
            observationCount: 0,
            feedbackCount: 0,
        );
    }

    /**
     * Find one of a game's experiments by id, across all its iterations.
     *
     * Used when a decision cites an experiment: the citation arrives as a bare id
     * in a request body, and resolving it through the game is what stops one
     * studio's decision from citing another's experiment. The same shape as the
     * prototype-version lookup, for the same reason.
     */
    public function findExperimentOfGame(Game $game, string $experimentId): ?DesignExperiment
    {
        return DesignExperiment::query()
            ->whereKey($experimentId)
            ->whereHas('iteration', fn (Builder $query) => $query->where('game_id', $game->getKey()))
            ->first();
    }

    /**
     * Narrow an iteration query by what somebody typed.
     *
     * Title, objective, hypothesis and summary — the four places a designer's own
     * words end up. Case folded on both sides so a search behaves the same
     * whatever the database's collation is.
     *
     * @param  Builder<Iteration>  $query
     * @return Builder<Iteration>
     */
    private function applySearch(Builder $query, string $term): Builder
    {
        $pattern = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term)).'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->whereRaw('LOWER(title) LIKE ? ESCAPE ?', [$pattern, '\\'])
                ->orWhereRaw('LOWER(objective) LIKE ? ESCAPE ?', [$pattern, '\\'])
                ->orWhereRaw("LOWER(COALESCE(hypothesis, '')) LIKE ? ESCAPE ?", [$pattern, '\\'])
                ->orWhereRaw("LOWER(COALESCE(summary, '')) LIKE ? ESCAPE ?", [$pattern, '\\']);
        });
    }

    /**
     * Give every iteration in a game-scoped list the same game object.
     *
     * Each iteration is rendered with what the caller may do to it, and every one
     * of those answers needs the caller's access to the game. Left alone, Eloquent
     * would lazily load a separate game per iteration and resolve the same
     * workspace membership once per row; they all belong to the game that was
     * passed in, so handing them the instance already in hand collapses the whole
     * list onto one membership lookup.
     *
     * @param  Collection<int, Iteration>  $iterations
     * @return Collection<int, Iteration>
     */
    private function withGame(Game $game, Collection $iterations): Collection
    {
        return $iterations->each(fn (Iteration $iteration) => $iteration->setRelation('game', $game));
    }
}
