<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Support\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\ValueObjects\CitedEvidence;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * A decision's citations, resolved into things a reader can actually read.
 *
 * The query behind section 45's evidence panel, and the place where "do not duplicate the
 * evidence" gets paid for. A stored citation is a type and a bare id; what a reader needs is
 * "Observation: players spent less time waiting", with somewhere to click. So each row is
 * resolved here, at render time, through whoever owns it.
 *
 * Reading fresh rather than storing a copy is the whole design. A copy taken at citation time
 * would leave a decision quoting words the observation no longer contains after somebody
 * corrected it — a quiet, permanent inaccuracy in the record the module exists to keep. Read
 * live, a correction appears in every decision that cited it.
 *
 * The cost is a lookup per citation, which is affordable because a decision cites a handful
 * of things. If a studio ever cites forty, the answer is to batch by type in the adapter
 * rather than to start caching excerpts.
 *
 * Anything that fails to resolve comes back visibly unresolved rather than being dropped. The
 * commonest cause is not deletion but permission — a reader who can see the iteration and not
 * the playtest — and saying so beats a shorter list that reads as "nothing supported this".
 */
final class GetDecisionEvidence
{
    public function __construct(
        private readonly IterationRepository $iterations,
        private readonly PlaytestEvidence $playtesting,
    ) {}

    /**
     * @return Collection<int, CitedEvidence>
     */
    public function handle(DesignDecision $decision): Collection
    {
        $game = $decision->iteration?->game;

        return Collection::make($this->iterations->evidenceOf($decision)->all())
            ->map(fn (DecisionEvidence $evidence): CitedEvidence => $this->resolve($game, $evidence))
            ->values();
    }

    /**
     * Turn one stored citation into a readable exhibit.
     */
    private function resolve(?Game $game, DecisionEvidence $evidence): CitedEvidence
    {
        if ($evidence->type === EvidenceType::Note) {
            return CitedEvidence::note($evidence->getKey(), $evidence->description);
        }

        $referenceId = $evidence->reference_id;

        if ($game === null || $referenceId === null) {
            return CitedEvidence::unresolved(
                $evidence->getKey(),
                $evidence->type,
                $referenceId,
                $evidence->description,
            );
        }

        return $evidence->type === EvidenceType::Experiment
            ? $this->resolveExperiment($game, $evidence, $referenceId)
            : $this->resolvePlaytesting($game, $evidence, $referenceId);
    }

    /**
     * Resolve a citation of one of this game's own experiments.
     *
     * The excerpt is the question rather than the result, because a citation of an experiment
     * is a citation of the enquiry — and the question is what identifies which one somebody
     * means. The conclusion, where there is one, is the attribution beside it: the sentence
     * that actually does the supporting.
     */
    private function resolveExperiment(Game $game, DecisionEvidence $evidence, string $referenceId): CitedEvidence
    {
        $experiment = $this->iterations->findExperimentOfGame($game, $referenceId);

        if ($experiment === null) {
            return CitedEvidence::unresolved(
                $evidence->getKey(),
                $evidence->type,
                $referenceId,
                $evidence->description,
            );
        }

        return new CitedEvidence(
            id: $evidence->getKey(),
            type: $evidence->type,
            typeLabel: $evidence->type->label(),
            referenceId: $referenceId,
            description: $evidence->description,
            excerpt: $experiment->question,
            attribution: $experiment->conclusion,
        );
    }

    /**
     * Resolve a citation of a playtest, an observation or a piece of feedback.
     *
     * Everything about the cited record comes back from the adapter, including the playtest it
     * belongs to — which is what lets the interface link an observation back into Playtesting
     * without this module publishing a route for somebody else's records.
     */
    private function resolvePlaytesting(Game $game, DecisionEvidence $evidence, string $referenceId): CitedEvidence
    {
        $resolved = $this->playtesting->excerptFor($game, $evidence->type, $referenceId);

        if ($resolved === null) {
            return CitedEvidence::unresolved(
                $evidence->getKey(),
                $evidence->type,
                $referenceId,
                $evidence->description,
            );
        }

        return new CitedEvidence(
            id: $evidence->getKey(),
            type: $evidence->type,
            typeLabel: $evidence->type->label(),
            referenceId: $referenceId,
            description: $evidence->description,
            excerpt: $resolved['excerpt'],
            attribution: $resolved['attribution'],
            playtestId: $resolved['playtest_id'],
        );
    }
}
