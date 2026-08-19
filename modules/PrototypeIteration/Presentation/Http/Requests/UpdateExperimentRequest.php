<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DesignExperimentData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Refining an experiment's design before it is answered.
 *
 * The guard behind this request protects the module's subtlest invariant: a completed experiment
 * refuses every field here. Editing a prediction after the result is known is how it becomes
 * retroactively correct — almost always honestly, by somebody tidying up sloppy wording — and the
 * tidied version then reads as a successful prediction forever.
 */
class UpdateExperimentRequest extends PrototypeIterationRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->experimentTitleRules(),
            'question' => $this->experimentQuestionRules(),
            'hypothesis' => $this->experimentProseRules(),
            'method' => $this->experimentProseRules(),
            'expected_result' => $this->experimentProseRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): DesignExperimentData
    {
        return DesignExperimentData::fromArray($this->validated());
    }
}
