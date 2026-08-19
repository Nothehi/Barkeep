<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\CreatePrototypeData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreatePrototypeRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGameForPrototypes('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The game is absent because it is not an input: it comes from the resolved route binding.
     * The design version is the only identifier a caller supplies, and it is checked against that
     * same game — so a version from somebody else's project fails here rather than becoming a
     * prototype that claims to implement a design nobody was working on.
     *
     * There is no rule for the status. Every prototype starts as a draft, and anything sent would
     * be ignored — so it is not accepted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'game_version_id' => $this->gameVersionRules($this->game()),
            'name' => $this->prototypeNameRules(),
            'description' => $this->prototypeDescriptionRules(),
            'type' => $this->prototypeTypeRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreatePrototypeData
    {
        return CreatePrototypeData::fromArray($this->validated());
    }
}
