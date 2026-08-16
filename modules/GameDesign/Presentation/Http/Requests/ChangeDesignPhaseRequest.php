<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\Validation\GameValidationRules;
use Modules\GameDesign\Domain\Enums\DesignPhase;

class ChangeDesignPhaseRequest extends GameRequest
{
    use GameValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('changeDesignPhase');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'design_phase' => $this->designPhaseRules(),
        ];
    }

    /**
     * The phase the caller is recording the game as having reached.
     */
    public function designPhase(): DesignPhase
    {
        return DesignPhase::from((string) $this->validated('design_phase'));
    }
}
