<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Modules\PrototypeIteration\Application\DTOs\IterationTimeline;
use Modules\PrototypeIteration\Application\DTOs\TimelineEntry;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Enums\TimelineEntryKind;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\ValueObjects\PlaytestReference;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * A design cycle as it happened, on one axis.
 *
 * The module's primary interaction — section 41 — and the reason it is a query rather than a
 * table. Five kinds of record from four of this module's tables and one other bounded context
 * are gathered, flattened into already-worded entries, and sorted. Nothing is persisted:
 * there is no timeline table, no event log and no denormalised feed, because a stored sequence
 * that can disagree with the rows it describes is a sequence somebody will eventually have to
 * reconcile by hand.
 *
 * It is affordable because a cycle is small — a handful of changes, a couple of experiments, a
 * few playtests, one or two decisions. A feed across a whole studio is a reporting problem for
 * the analytics capability rather than a reason to materialise this.
 *
 * ## Why the lifecycle is on the line
 *
 * The cycle's own start and end are entries, not chrome around them. "The decision was taken
 * four days after the playtest and two hours before the cycle closed" is a fact about how a
 * studio works, and it is only visible when the boundaries share the axis with the work. It is
 * also what makes the outcome the last thing a reader sees, in the shape section 41 sets out.
 *
 * ## Why entries are flat
 *
 * A `TimelineEntry` carries strings rather than the record it came from. An entry holding its
 * source would hand the presentation layer a five-way union to destructure and would put
 * Playtesting's model within reach of a resource — and the wording would then have to be
 * derived on the client, where it would drift from the enums that define it.
 *
 * ## Ordering
 *
 * Everything undated sorts to the end. A change recorded from notes after the fact has only
 * the time it was written down; putting the epilogue before the game is the one ordering
 * mistake a timeline must not make.
 */
final class GetIterationTimeline
{
    public function __construct(
        private readonly IterationRepository $iterations,
        private readonly PlaytestEvidence $playtesting,
    ) {}

    public function handle(Iteration $iteration): IterationTimeline
    {
        return IterationTimeline::of($iteration, [
            ...$this->lifecycleStart($iteration),
            ...$this->changes($iteration),
            ...$this->experiments($iteration),
            ...$this->playtests($iteration),
            ...$this->decisions($iteration),
            ...$this->lifecycleEnd($iteration),
        ]);
    }

    /**
     * The moment the work began, if it has.
     *
     * A planned cycle contributes nothing here, which is what lets the screen offer "start this
     * iteration" against an empty axis rather than drawing a line with one dot on it.
     *
     * @return list<TimelineEntry>
     */
    private function lifecycleStart(Iteration $iteration): array
    {
        if ($iteration->started_at === null) {
            return [];
        }

        return [new TimelineEntry(
            kind: TimelineEntryKind::Started,
            id: $iteration->getKey(),
            title: $iteration->objective,
            at: $iteration->started_at->toDateTimeImmutable(),
        )];
    }

    /**
     * How the cycle ended, if it has.
     *
     * The outcome is the badge and the summary is the body, which puts the four-word verdict and
     * the account of it in the same entry — the shape a reader wants at the bottom of a cycle.
     *
     * A cancelled iteration has no `completed_at`, because nothing was completed. It is dated
     * from when the row was last written, which is when somebody cancelled it — the closest
     * honest answer available without storing a `cancelled_at` that only this one entry would
     * ever read.
     *
     * @return list<TimelineEntry>
     */
    private function lifecycleEnd(Iteration $iteration): array
    {
        if ($iteration->status === IterationStatus::Completed) {
            return [new TimelineEntry(
                kind: TimelineEntryKind::Completed,
                id: $iteration->getKey(),
                title: $iteration->outcome?->label() ?? TimelineEntryKind::Completed->label(),
                at: $iteration->completed_at?->toDateTimeImmutable(),
                body: $iteration->summary,
                badge: $iteration->outcome?->label(),
                status: $iteration->outcome?->value,
            )];
        }

        if ($iteration->status === IterationStatus::Cancelled) {
            return [new TimelineEntry(
                kind: TimelineEntryKind::Cancelled,
                id: $iteration->getKey(),
                title: TimelineEntryKind::Cancelled->label(),
                at: $iteration->updated_at?->toDateTimeImmutable(),
            )];
        }

        return [];
    }

    /**
     * What was changed, and why.
     *
     * @return list<TimelineEntry>
     */
    private function changes(Iteration $iteration): array
    {
        return array_values($this->iterations->changesOf($iteration)
            ->map(fn (DesignChange $change): TimelineEntry => new TimelineEntry(
                kind: TimelineEntryKind::Change,
                id: $change->getKey(),
                title: $change->title,
                at: $change->created_at?->toDateTimeImmutable(),
                /*
                 * The reason rather than the description, when there is a choice. A timeline
                 * is scanned, and "reaction windows created excessive downtime" is the line
                 * that makes the entry worth reading — the description repeats the title more
                 * often than it adds to it.
                 */
                body: $change->reason,
                badge: $change->category->label(),
                status: $change->category->value,
            ))
            ->all());
    }

    /**
     * What was tried, and what came of it.
     *
     * Dated from when the experiment ran rather than from when it was designed, because an
     * experiment's place in the sequence is when it happened. A planned experiment falls back
     * to when it was written down, which puts an unrun question in the timeline at the point
     * somebody thought of it — where it belongs.
     *
     * @return list<TimelineEntry>
     */
    private function experiments(Iteration $iteration): array
    {
        return array_values($this->iterations->experimentsOf($iteration)
            ->map(fn (DesignExperiment $experiment): TimelineEntry => new TimelineEntry(
                kind: TimelineEntryKind::Experiment,
                id: $experiment->getKey(),
                title: $experiment->title,
                at: ($experiment->started_at ?? $experiment->created_at)?->toDateTimeImmutable(),
                /*
                 * What was found where there is a finding, and what was asked where there is
                 * not. An experiment appears on the timeline before it has an answer, and the
                 * question is what makes the entry legible in the meantime.
                 */
                body: $experiment->actual_result ?? $experiment->question,
                badge: $experiment->status->label(),
                status: $experiment->status->value,
            ))
            ->all());
    }

    /**
     * The evidence the cycle was tested through.
     *
     * Read through the Playtesting adapter, so the counts here are Playtesting's own at the
     * moment of the query. Dated from when the playtest was attached rather than from when it
     * ran: this module knows the first for certain and would have to ask for the second, and
     * for a timeline of *design work* the moment the studio brought the evidence in is the
     * relevant one.
     *
     * @return list<TimelineEntry>
     */
    private function playtests(Iteration $iteration): array
    {
        $game = $iteration->game;

        if ($game === null) {
            return [];
        }

        $links = $this->iterations->playtestLinksOf($iteration);

        return array_values($this->playtesting->referencesFor($game, $links)
            ->map(fn (PlaytestReference $reference): TimelineEntry => new TimelineEntry(
                kind: TimelineEntryKind::Playtest,
                id: $reference->linkId,
                title: $reference->title,
                at: $reference->attachedAt,
                badge: $reference->statusLabel,
                status: $reference->status,
                reference: $reference->playtestId,
                counts: $this->playtestCounts($reference),
            ))
            ->all());
    }

    /**
     * The numbers a playtest entry shows under its title.
     *
     * Handed over as numbers rather than as a sentence, because pluralisation in this
     * application happens on the client against the shared catalogue — no PHP here builds
     * ":count observations", and one that did would be the only place in the platform doing it.
     *
     * Null when the playtest produced nothing, so an attached playtest that has not been run
     * yet does not report "0 observations" as though that were a finding.
     *
     * @return array<string, int>|null
     */
    private function playtestCounts(PlaytestReference $reference): ?array
    {
        if (! $reference->hasEvidence()) {
            return null;
        }

        return [
            'sessions' => $reference->sessionCount,
            'observations' => $reference->observationCount,
            'feedback' => $reference->feedbackCount,
        ];
    }

    /**
     * What was concluded.
     *
     * Dated from when the decision was settled where it has been, and from when it was proposed
     * where it has not. That is the pair of moments that matters in a design record: an open
     * proposal sits in the sequence where somebody raised it, and an agreed one moves to where
     * the studio committed.
     *
     * @return list<TimelineEntry>
     */
    private function decisions(Iteration $iteration): array
    {
        return array_values($this->iterations->decisionsOf($iteration)
            ->map(fn (DesignDecision $decision): TimelineEntry => new TimelineEntry(
                kind: TimelineEntryKind::Decision,
                id: $decision->getKey(),
                title: $decision->decision,
                at: ($decision->decided_at ?? $decision->created_at)?->toDateTimeImmutable(),
                body: $decision->reason,
                badge: $decision->status->label(),
                status: $decision->status->value,
            ))
            ->all());
    }
}
