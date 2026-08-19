<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\CompleteExperimentData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Recording what an experiment actually produced.
 *
 * The only request that may write the after half of an experiment, which is what makes the before
 * half trustworthy: the result arrives through a different door from the prediction, after the
 * experiment has been run.
 */
class CompleteExperimentRequest extends PrototypeIterationRequest
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
     * The result is required and the conclusion is not, and the asymmetry is real rather than
     * lenient. What happened is a fact the person at the table already has; what it means usually
     * arrives days later, after somebody has read the observations back. Requiring both in one
     * request would produce conclusions written to fill a field.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'actual_result' => $this->experimentResultRules(),
            'conclusion' => $this->experimentConclusionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CompleteExperimentData
    {
        return CompleteExperimentData::fromArray($this->validated());
    }
}
