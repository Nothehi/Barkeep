<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\DesignChangeData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreateDesignChangeRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `recordWork` rather than an ability of its own, because "may design work be added to this
     * cycle?" is one question with one answer — and giving it one name is what stops the eight
     * writes on the iteration screen from drifting apart as the rules change.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('recordWork');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The reason is required and floored, and this is the request where that earns its keep. A
     * studio in a hurry will happily record "reduced starting resources to 3" and move on; six
     * months later that entry answers nothing, because the number is visible in the rules and the
     * argument is not.
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
