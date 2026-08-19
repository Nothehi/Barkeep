<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DecisionEvidenceData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;

/**
 * Citing something in support of a decision.
 *
 * The request where the deliberately weak evidence reference is made safe. The stored column has
 * no foreign key — that is the price of not duplicating Playtesting's evidence and not holding a
 * key into another context's tables — so the id is checked here, and always through the game the
 * decision belongs to.
 *
 * That scoping is the security property. Without it a bare uuid in this body would let a decision
 * in one studio cite an observation from another's playtest, and the citation would then render as
 * genuine supporting evidence on their screen.
 */
class CreateEvidenceRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('recordWork');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The reference rule is built with the submitted type, because whether a reference is needed —
     * and what it is checked against — depends on it. A note needs none; everything else names
     * something owned by Playtesting or by this module. Handing the type to the rule keeps one
     * message on the form instead of two contradicting each other.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => $this->evidenceTypeRules(),
            'reference_id' => $this->evidenceReferenceRules($this->game(), $this->submittedType()),
            'description' => $this->evidenceDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): DecisionEvidenceData
    {
        return DecisionEvidenceData::fromArray($this->validated());
    }

    /**
     * The type the caller submitted, if it names one at all.
     *
     * Read before validation on purpose, which is why it has to tolerate rubbish: the reference
     * rule needs to know the type in order to decide what to check, and it is built while the
     * rules are being assembled. A value that names nothing comes back as null, and the rule then
     * stays quiet — leaving the type's own rule to report the one real error rather than putting
     * two messages on the form for one mistake.
     */
    private function submittedType(): ?EvidenceType
    {
        $type = $this->input('type');

        return is_string($type) ? EvidenceType::tryFrom($type) : null;
    }
}
