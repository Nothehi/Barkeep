<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\DTOs\GameFilters;
use Modules\GameDesign\Application\Validation\GameValidationRules;
use Modules\GameDesign\Domain\Models\Game;

/**
 * Reading the games in a workspace, optionally narrowed.
 *
 * The filters cannot widen the list: the workspace scope is applied by the
 * query itself and is not something this request can express.
 */
class GameFilterRequest extends GameRequest
{
    use GameValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('viewAny', [Game::class, $this->workspace()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->gameFilterRules();
    }

    /**
     * Get the validated query string as an application layer DTO.
     */
    public function toFilters(): GameFilters
    {
        return GameFilters::fromArray($this->validated());
    }
}
