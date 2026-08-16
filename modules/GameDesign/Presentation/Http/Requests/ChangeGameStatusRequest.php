<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\Validation\GameValidationRules;
use Modules\GameDesign\Domain\Enums\GameStatus;

class ChangeGameStatusRequest extends GameRequest
{
    use GameValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('changeStatus');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validation establishes only that the value names a real status.
     * Whether this game may move to it is decided by the transition matrix in
     * the domain, which is the one place that knows where the game is coming
     * from.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => $this->gameStatusRules(),
        ];
    }

    /**
     * The status the caller is asking the game to move to.
     */
    public function status(): GameStatus
    {
        return GameStatus::from((string) $this->validated('status'));
    }
}
