<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CreateObservationData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Correcting an observation while the session is still open.
 *
 * The whole observation is replaced rather than patched: every field is on the
 * form, so a partial update would only add a way for the two to disagree.
 */
class UpdateObservationRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('manageObservations');
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
