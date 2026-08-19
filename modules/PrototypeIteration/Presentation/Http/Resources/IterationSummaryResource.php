<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PrototypeIteration\Application\DTOs\IterationSummary;

/**
 * What a design cycle produced, as figures.
 *
 * Wraps the summary DTO rather than a model, which is why the resource reads as a list of
 * counts: every one of them is derived on read, and nothing here is a column. See
 * `IterationSummary` for why none of it is persisted.
 *
 * The three pairs are the point of this shape. `experiments` against
 * `completed_experiments` shows a cycle that closed with questions still open — this module
 * refuses to complete an experiment on the iteration's behalf, so the gap is real and the
 * interface uses it to warn before completion. `decisions` against `accepted_decisions` shows
 * a cycle that proposed conclusions and agreed none. `playtests` against the observation and
 * feedback totals shows evidence that was attached but never produced anything.
 *
 * Two derived booleans travel with them so the interface does not re-derive the same
 * comparisons: `has_work` decides whether a summary is worth drawing, and
 * `experiments_settled` decides whether to point out unfinished experiments before a cycle is
 * closed.
 *
 * @mixin IterationSummary
 */
class IterationSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'iteration_id' => $this->iteration->id,
            'status' => $this->iteration->status->value,
            'status_label' => $this->iteration->status->label(),
            'outcome' => $this->iteration->outcome?->value,
            'outcome_label' => $this->iteration->outcome?->label(),
            'summary' => $this->iteration->summary,
            'objective' => $this->iteration->objective,
            'hypothesis' => $this->iteration->hypothesis,
            'changes' => $this->changeCount,
            'experiments' => $this->experimentCount,
            'completed_experiments' => $this->completedExperimentCount,
            'decisions' => $this->decisionCount,
            'accepted_decisions' => $this->acceptedDecisionCount,
            'evidence' => $this->evidenceCount,
            'playtests' => $this->playtestCount,
            'sessions' => $this->sessionCount,
            'observations' => $this->observationCount,
            'feedback' => $this->feedbackCount,
            'has_work' => $this->hasWork(),
            'has_evidence' => $this->hasEvidence(),
            'experiments_settled' => $this->experimentsAreSettled(),
        ];
    }
}
