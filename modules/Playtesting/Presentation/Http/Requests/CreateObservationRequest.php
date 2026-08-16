<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CreateObservationData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Recording something noticed during a session.
 *
 * Only the text is required, and that is the whole shape of this form. An
 * observation is typed with one hand while the game carries on with the
 * other; every field that has to be filled in first is a reason the
 * observation does not get recorded at all.
 *
 * The participant is the one identifier here that did not arrive through a
 * route binding, so it is checked against the session rather than trusted.
 */
class CreateObservationRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('createObservation');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => $this->observationContentRules(),
            'category' => $this->observationCategoryRules(),
            'participant_id' => $this->participantReferenceRules($this->playtestSession()),
            'observed_at' => $this->observedAtRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateObservationData
    {
        return CreateObservationData::fromArray($this->validated());
    }
}
