<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CreatePlaytestData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

class CreatePlaytestRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The game is absent because it is not an input: it comes from the
     * resolved route binding. The version is the only identifier a caller
     * supplies, and it is checked against that same game — so a version from
     * somebody else's project fails here rather than becoming a playtest that
     * describes an evening nobody had.
     *
     * There is no rule for the status either. Every playtest starts planned,
     * and anything sent would be ignored — so it is not accepted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'game_version_id' => $this->gameVersionRules($this->game()),
            'title' => $this->playtestTitleRules(),
            'objective' => $this->playtestObjectiveRules(),
            'hypothesis' => $this->playtestHypothesisRules(),
            'planned_at' => $this->plannedAtRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreatePlaytestData
    {
        return CreatePlaytestData::fromArray($this->validated());
    }
}
