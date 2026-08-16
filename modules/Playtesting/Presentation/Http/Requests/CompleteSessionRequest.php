<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CompleteSessionData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Ending a session, with what it settled.
 *
 * Both fields are optional. Ending a session happens while people are standing
 * up and putting the box away; a dialog that demands a write-up first is a
 * dialog that gets dismissed, and the session never gets ended at all.
 *
 * That does mean the outcome has to be written now or not at all, because a
 * completed session is closed. The playtest's own conclusion is the field that
 * stays open afterwards, and it is where the considered version belongs.
 */
class CompleteSessionRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('complete');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'outcome' => $this->sessionOutcomeRules(),
            'notes' => $this->sessionNotesRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CompleteSessionData
    {
        return CompleteSessionData::fromArray($this->validated());
    }
}
