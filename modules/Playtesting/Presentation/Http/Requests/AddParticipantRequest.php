<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\AddParticipantData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Seating somebody at a session.
 *
 * A name is enough, and the ordering of the rules says why: the display name
 * is required and the account is not, because most people at a playtest have
 * no Barkeep account. Requiring one would either stop the playtest being
 * recorded or produce a user table full of people who never signed up.
 *
 * When an account *is* given it has to belong to the workspace — not a rule
 * about who may play, but about disclosure, since linking one makes its name
 * and address readable through the participant list.
 */
class AddParticipantRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('manageParticipants');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => $this->displayNameRules(),
            'role' => $this->participantRoleRules(),
            'user_id' => $this->participantAccountRules($this->game()),
            'joined_at' => $this->joinedAtRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): AddParticipantData
    {
        return AddParticipantData::fromArray($this->validated());
    }
}
