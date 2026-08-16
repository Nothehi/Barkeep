<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CreateSessionData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Scheduling another sitting of a playtest.
 *
 * Every field is optional, which is a usability decision with teeth: the
 * common case is a designer about to start a session in the next thirty
 * seconds, and a form that insists on a location first is a form that gets
 * abandoned — after which the session gets run without being recorded.
 *
 * The real timestamps are not accepted at all. When a session started and
 * ended is written by the commands that start and end it, from the clock.
 */
class CreateSessionRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPlaytest('createSession');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'planned_at' => $this->plannedAtRules(),
            'location' => $this->sessionLocationRules(),
            'notes' => $this->sessionNotesRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateSessionData
    {
        return CreateSessionData::fromArray($this->validated());
    }
}
