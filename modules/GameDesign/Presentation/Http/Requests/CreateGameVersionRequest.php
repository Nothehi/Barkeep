<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\DTOs\CreateGameVersionData;
use Modules\GameDesign\Application\Validation\GameValidationRules;

class CreateGameVersionRequest extends GameRequest
{
    use GameValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('createVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * There is no rule for the version number because there is no field for
     * one. Numbers are allocated in sequence by the application layer, and
     * anything a caller sent would be ignored — so it is not accepted in the
     * first place.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->versionNameRules(),
            'description' => $this->versionDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateGameVersionData
    {
        return CreateGameVersionData::fromArray($this->validated());
    }
}
