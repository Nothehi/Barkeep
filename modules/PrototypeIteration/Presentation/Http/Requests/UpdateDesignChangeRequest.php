<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DesignChangeData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Rewording a change while its cycle is still open.
 *
 * A whole-record replacement rather than a partial update, so every field is required exactly as
 * it is on creation. A change is four short fields, and the machinery to express "leave the
 * description alone" would be larger than the thing it operated on.
 */
class UpdateDesignChangeRequest extends PrototypeIterationRequest
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
            'category' => $this->changeCategoryRules(),
            'title' => $this->changeTitleRules(),
            'description' => $this->changeDescriptionRules(),
            'reason' => $this->changeReasonRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): DesignChangeData
    {
        return DesignChangeData::fromArray($this->validated());
    }
}
