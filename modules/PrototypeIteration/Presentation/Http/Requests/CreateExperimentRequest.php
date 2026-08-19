<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DesignExperimentData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreateExperimentRequest extends PrototypeIterationRequest
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
     * Only the before half of an experiment is accepted here, and there is no rule for
     * `actual_result` or `conclusion` — which means a request cannot invent a prediction and its
     * confirmation in one go. Recording what happened has its own endpoint, reachable only after
     * the experiment has been run.
     *
     * Only the question is required. Exploratory work is real work, and demanding a hypothesis
     * for "let us run it and watch" would produce invented predictions — worse than none, because
     * an invented prediction that happens to come true reads as insight.
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
