<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\DTOs\UpdateGameData;
use Modules\GameDesign\Application\Validation\GameValidationRules;

class UpdateGameRequest extends GameRequest
{
    use GameValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Uniqueness is checked inside the game's own workspace, ignoring the
     * game itself so that saving the form without touching the address is not
     * a collision with the game's current one.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $game = $this->game();

        return $this->gameRules($game->workspace_id, $game->getKey());
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdateGameData
    {
        return UpdateGameData::fromArray($this->validated());
    }
}
