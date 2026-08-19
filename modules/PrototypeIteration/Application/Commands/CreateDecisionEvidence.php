<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DecisionEvidenceData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Exceptions\UnknownEvidenceReference;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * Cite something in support of a decision.
 *
 * The command that makes the deliberately weak evidence reference safe. The stored column
 * has no foreign key — that is the price of not duplicating Playtesting's evidence and not
 * holding a key into another context's tables — so the id is proved here, on the way in,
 * and always *through the decision's own game*.
 *
 * That scoping is the security property and it is the whole reason this command is not
 * three lines. Without it a bare uuid in a request body would let a decision in one studio
 * cite an observation from another's playtest, and the citation would then render on their
 * screen as genuine supporting evidence. With it, an id from elsewhere is
 * indistinguishable from an id that names nothing — worded the same way, so that a
 * citation cannot be used to discover what other studios are working on.
 *
 * Resolution goes through whoever owns the type: playtests, observations and feedback
 * through the Playtesting adapter's game-scoped queries, experiments through this module's
 * own repository. Nothing here writes a query against another context's schema.
 *
 * ## Notes
 *
 * A note is the exception at both ends: it needs no reference because it *is* the
 * evidence, and passing one alongside a note is refused rather than ignored. Storing a
 * reference nobody will ever resolve would leave a row whose type says "not a pointer" and
 * whose data says otherwise — and the next person to read the table would have to work out
 * which half to trust.
 *
 * ## What is never stored
 *
 * The cited words. "Players spent less time waiting" belongs to the observation in
 * Playtesting; the description here is the *reason somebody thought that observation
 * supported this decision*, which is the part of a citation that would otherwise be lost.
 * The excerpt is read live at render time, so a correction to an observation appears in
 * every decision that cited it.
 */
final class CreateDecisionEvidence
{
    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly PlaytestEvidence $playtesting,
        private readonly IterationRepository $iterations,
    ) {}

    public function handle(User $creator, DesignDecision $decision, DecisionEvidenceData $data): DecisionEvidence
    {
        /*
         * Evidence may be added while the decision is still open, and no longer once it is
         * settled. Citing something in support of an agreement that has already been
         * reached would change what the record says the studio decided *on* — which is the
         * same rule that freezes the decision's own wording.
         */
        $this->guard->ensureDecisionIsModifiable($decision);

        $referenceId = $this->resolveReference($decision, $data);

        $evidence = new DecisionEvidence;

        $evidence->fill(['description' => $data->description]);

        $evidence->decision_id = $decision->getKey();
        $evidence->type = $data->type;
        $evidence->reference_id = $referenceId;
        $evidence->created_by = $creator->id;

        $evidence->save();

        $evidence->setRelation('decision', $decision);
        $evidence->setRelation('creator', $creator);

        return $evidence;
    }

    /**
     * Prove the citation points at something real in this game, and return its id.
     *
     * Returns null for a note, which carries no reference at all.
     *
     * @throws UnknownEvidenceReference
     */
    private function resolveReference(DesignDecision $decision, DecisionEvidenceData $data): ?string
    {
        if (! $data->type->requiresReference()) {
            if ($data->referenceId !== null) {
                /*
                 * Refused rather than silently dropped. A row whose type says "not a
                 * pointer" and whose data holds one is a row the next reader has to
                 * adjudicate, and the caller has plainly misunderstood the shape.
                 */
                throw UnknownEvidenceReference::forReference($data->type, $data->referenceId);
            }

            return null;
        }

        $referenceId = $data->referenceId;

        if ($referenceId === null) {
            throw UnknownEvidenceReference::missingFor($data->type);
        }

        $game = $decision->iteration?->game;

        if ($game === null || ! $this->resolves($game, $data->type, $referenceId)) {
            throw UnknownEvidenceReference::forReference($data->type, $referenceId);
        }

        return $referenceId;
    }

    /**
     * Ask whoever owns the type whether the reference names one of this game's records.
     */
    private function resolves(Game $game, EvidenceType $type, string $referenceId): bool
    {
        return match ($type) {
            EvidenceType::Playtest => $this->playtesting->gameHasPlaytest($game, $referenceId),
            EvidenceType::Observation => $this->playtesting->gameHasObservation($game, $referenceId),
            EvidenceType::Feedback => $this->playtesting->gameHasFeedback($game, $referenceId),
            EvidenceType::Experiment => $this->iterations->findExperimentOfGame($game, $referenceId) !== null,
            EvidenceType::Note => true,
        };
    }
}
