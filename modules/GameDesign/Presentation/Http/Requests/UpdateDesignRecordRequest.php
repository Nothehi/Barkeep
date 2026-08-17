<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\DTOs\DesignRecordData;
use Modules\GameDesign\Application\Validation\GameValidationRules;
use Modules\GameDesign\Application\Validation\MechanicValidationRules;

class UpdateDesignRecordRequest extends GameRequest
{
    use GameValidationRules, MechanicValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `updateDesignRecord` rather than `update`, even though both currently
     * answer the same way. Deciding a player count and renaming a project are
     * different acts, and the place they will come apart — a game shared with a
     * reviewer who may read the design and not edit the project — is one the
     * policy should already have a seam for.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('updateDesignRecord');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Two traits, because the mechanics selection is about the shared vocabulary
     * rather than about this game. The ids are checked for shape only — whether
     * they name terms that exist and are still offered is
     * `UpdateDesignRecord`'s decision, which has to load them anyway.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->designRecordRules(),
            ...$this->mechanicSelectionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     *
     * The ranges become value objects here, so a backwards player count is
     * refused before the command runs — and refused with the domain's own
     * wording rather than a generic "must be greater than" message.
     */
    public function toData(): DesignRecordData
    {
        return DesignRecordData::fromArray($this->validated());
    }
}
