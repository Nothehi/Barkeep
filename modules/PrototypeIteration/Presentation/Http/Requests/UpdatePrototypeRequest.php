<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\UpdatePrototypeData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class UpdatePrototypeRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPrototype('update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Neither the status nor the design version is accepted, and both absences are rules rather
     * than omissions. Archiving is an action with its own endpoint, so an irreversible move is
     * never one field value away from a reversible one; and a prototype records the design state
     * it was built from, so rewriting that would change what every iteration against it claims to
     * have been working with.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->prototypeNameRules(required: false),
            'description' => $this->prototypeDescriptionRules(),
            'type' => $this->prototypeTypeRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdatePrototypeData
    {
        return UpdatePrototypeData::fromArray($this->validated());
    }
}
