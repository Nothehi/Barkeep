<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DesignDecisionData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Rewording a decision that is still open.
 *
 * Open means proposed or deferred — a decision still being argued about. The guard refuses the
 * moment it is accepted or rejected, because editing the text of a settled decision changes what
 * the design history says the studio agreed to.
 *
 * That is also why there is no reversal request anywhere in this module: not as an edit here, and
 * not as a transition. A studio that has changed its mind records a new decision in a later cycle
 * saying so.
 */
class UpdateDecisionRequest extends PrototypeIterationRequest
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
            'title' => $this->decisionTitleRules(),
            'decision' => $this->decisionStatementRules(),
            'reason' => $this->decisionReasonRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): DesignDecisionData
    {
        return DesignDecisionData::fromArray($this->validated());
    }
}
