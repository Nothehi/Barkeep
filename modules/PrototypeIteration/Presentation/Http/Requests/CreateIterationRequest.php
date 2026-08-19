<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\CreateIterationData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreateIterationRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGameForIterations('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Two identifiers arrive in the body, and both are checked against the game from the route.
     * That pair of rules is the module's central invariant at the form boundary — and the
     * prototype version is the one that matters most, because a mismatch there is invisible
     * outside this module: the iteration would read perfectly while describing work nobody did.
     *
     * There is no status, outcome or summary. Every cycle starts planned with nothing to say
     * about how it went, and completing one is an action with its own endpoint and its own
     * required arguments.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $game = $this->game();

        return [
            'game_version_id' => $this->gameVersionRules($game),
            'prototype_version_id' => $this->prototypeVersionRules($game),
            'title' => $this->iterationTitleRules(),
            'objective' => $this->iterationObjectiveRules(),
            'hypothesis' => $this->iterationHypothesisRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateIterationData
    {
        return CreateIterationData::fromArray($this->validated());
    }
}
