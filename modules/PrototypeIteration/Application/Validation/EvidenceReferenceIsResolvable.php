<?php

namespace Modules\PrototypeIteration\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * Check that a citation points at something real, in this game.
 *
 * The rule that makes the deliberately weak evidence reference safe. The stored
 * column has no foreign key — that is the price of not duplicating Playtesting's
 * evidence and not holding a key into another context's tables — so the id is
 * validated on the way in instead, and always *through the game*.
 *
 * That scoping is the security property. Without it, a bare uuid in a request body
 * would let a decision in one studio cite an observation from another's playtest,
 * and the citation would then render on their screen as a real piece of supporting
 * evidence. With it, an id from elsewhere is indistinguishable from an id that
 * names nothing — which is how every lookup failure in the platform is worded, so
 * that an id cannot be used to discover what other studios are working on.
 *
 * Only the types that need a reference are checked. A note *is* the evidence
 * rather than a pointer to it, and passing a reference alongside one is caught by
 * the command rather than here, because it is a coherence problem across two
 * fields rather than a fact about this one.
 *
 * Each type resolves through whoever owns it: playtests, observations and feedback
 * through Playtesting's own game-scoped queries via this module's adapter, and
 * experiments against this module's own tables. Nothing here writes a query against
 * another context's schema.
 */
class EvidenceReferenceIsResolvable implements ValidationRule
{
    /**
     * Run this rule even when the field is absent or null.
     *
     * Laravel skips a rule object on an empty value unless it says otherwise, and this rule has to see
     * the empty case: whether a reference is *required* depends on the type, and the type is only known
     * here. Without this flag a citation of type `observation` with no reference would pass validation
     * and be caught later by the command — reporting the same mistake as a 422 with no field attached,
     * which is the wrong place for a form to show it.
     */
    public bool $implicit = true;

    public function __construct(
        private readonly Game $game,
        private readonly ?EvidenceType $type,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /*
         * A type that did not itself validate leaves nothing to check against.
         * Reporting a second error for the reference would put two messages on a
         * form for one mistake.
         */
        if ($this->type === null) {
            return;
        }

        if (! $this->type->requiresReference()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail(__('Choose the :type this decision is based on.', [
                'type' => mb_strtolower($this->type->label()),
            ]));

            return;
        }

        if (! $this->resolves($this->type, $value)) {
            $fail(__('That :type cannot be found in this game.', [
                'type' => mb_strtolower($this->type->label()),
            ]));
        }
    }

    /**
     * Determine whether the reference names something in this game.
     *
     * Playtests, observations and feedback go through the Playtesting adapter, each
     * to its own game-scoped query — an observation is resolved as an observation
     * rather than being waved through on the strength of its playtest, so a citation
     * names the exact thing it claims to. Experiments resolve against this module's
     * own tables, still scoped to the game.
     */
    private function resolves(EvidenceType $type, string $referenceId): bool
    {
        $playtesting = app(PlaytestEvidence::class);

        return match ($type) {
            EvidenceType::Playtest => $playtesting->gameHasPlaytest($this->game, $referenceId),
            EvidenceType::Observation => $playtesting->gameHasObservation($this->game, $referenceId),
            EvidenceType::Feedback => $playtesting->gameHasFeedback($this->game, $referenceId),
            EvidenceType::Experiment => app(IterationRepository::class)
                ->findExperimentOfGame($this->game, $referenceId) !== null,
            EvidenceType::Note => true,
        };
    }
}
