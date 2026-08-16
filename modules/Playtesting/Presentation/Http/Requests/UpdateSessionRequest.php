<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\UpdateSessionData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Changing a session that has not ended.
 *
 * Notes are what this exists for: they accumulate as a session runs, so they
 * are saved repeatedly rather than once at the end.
 */
class UpdateSessionRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('update');
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
    public function toData(): UpdateSessionData
    {
        return UpdateSessionData::fromArray($this->validated());
    }
}
