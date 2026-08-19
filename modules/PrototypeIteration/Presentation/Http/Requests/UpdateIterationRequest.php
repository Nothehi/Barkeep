<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\UpdateIterationData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class UpdateIterationRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * A completed or cancelled cycle refuses this outright, which is section 53's historical
     * integrity rule reaching the HTTP door. The policy's message carries the reason, so a
     * caller is told which of the two endings the cycle reached rather than a bare no.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Both version ids are accepted here and re-checked against the game, because a designer who
     * picked the wrong build while planning should be able to fix it — and because an update is a
     * second door into the same invariant, so trusting an id that was validated once before would
     * be exactly where a mismatch got in.
     *
     * The outcome and summary are not accepted. Completing a cycle is an action, not a field.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $game = $this->game();

        return [
            'game_version_id' => $this->gameVersionRules($game, required: false),
            'prototype_version_id' => $this->prototypeVersionRules($game, required: false),
            'title' => $this->iterationTitleRules(required: false),
            'objective' => $this->iterationObjectiveRules(required: false),
            'hypothesis' => $this->iterationHypothesisRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdateIterationData
    {
        return UpdateIterationData::fromArray($this->validated());
    }
}
