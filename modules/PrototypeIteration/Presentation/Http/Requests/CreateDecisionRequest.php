<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DesignDecisionData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreateDecisionRequest extends PrototypeIterationRequest
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
     * All three fields required, which makes this the strictest create shape in the module. Each
     * does a distinct job at a distinct reading distance: the title is scanned in a list, the
     * decision is the sentence itself, and the reason is the argument. Drop the third and the
     * record becomes an instruction nobody can re-examine when the situation changes.
     *
     * There is no status. A decision starts proposed, and settling one is an action with its own
     * endpoint, its own attribution and its own event.
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
