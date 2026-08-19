<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\CompleteIterationData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Closing a design cycle.
 *
 * The only lifecycle request in the module that requires a body, and section 47 is the reason:
 * an outcome and a summary are both mandatory, and completion is refused without them. That is
 * enforced here so a designer filling in the dialog is told which field is missing, and again in
 * the command so a caller arriving another way is refused all the same.
 *
 * Neither field is nullable and the outcome has no default. An outcome that fell back to
 * something would record the platform's guess as the studio's own judgement.
 */
class CompleteIterationRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('complete');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'outcome' => $this->iterationOutcomeRules(),
            'summary' => $this->iterationSummaryRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CompleteIterationData
    {
        return CompleteIterationData::fromArray($this->validated());
    }
}
