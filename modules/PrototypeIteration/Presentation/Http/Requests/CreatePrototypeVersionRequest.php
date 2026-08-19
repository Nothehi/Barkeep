<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\CreatePrototypeVersionData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreatePrototypeVersionRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPrototype('createVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Both fields optional, and no version number accepted. Numbers are allocated by the module
     * in sequence, so no request can claim v999 or reuse a number three iterations already point
     * at — and the optionality is load-bearing rather than lax: the immutability rule is only
     * reasonable if cutting the next version is nearly free.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->prototypeVersionNameRules(),
            'description' => $this->prototypeVersionDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreatePrototypeVersionData
    {
        return CreatePrototypeVersionData::fromArray($this->validated());
    }
}
