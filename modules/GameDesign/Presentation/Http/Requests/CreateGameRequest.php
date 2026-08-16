<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\DTOs\CreateGameData;
use Modules\GameDesign\Application\Validation\GameValidationRules;
use Modules\GameDesign\Domain\Models\Game;

class CreateGameRequest extends GameRequest
{
    use GameValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Asked of the workspace from the route binding, which is the only place
     * a workspace is ever read from.
     */
    public function authorize(): Response
    {
        return $this->inspect('create', [Game::class, $this->workspace()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The address is optional here. Leaving it out asks the application layer
     * to derive one from the name; supplying it means that exact address, so
     * a collision is reported rather than worked around.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->gameRules($this->workspace()->getKey(), slugIsRequired: false),
            'design_phase' => $this->designPhaseRules(required: false),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateGameData
    {
        return CreateGameData::fromArray($this->validated());
    }
}
