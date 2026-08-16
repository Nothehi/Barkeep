<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\UpdatePlaytestData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Changing a playtest, whichever half of it is being changed.
 *
 * A playtest is not uniformly editable once it is over: its plan is frozen for
 * good and its conclusion stays open, because conclusions are written after
 * the sessions have happened. So the ability checked depends on what the
 * request actually touches — a caller writing only a conclusion is asking a
 * different question from one rewriting the objective, and a completed
 * playtest answers the two differently.
 */
class UpdatePlaytestRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Checked against the heavier of the two abilities the payload implies. A
     * request that touches the plan needs `update` whatever else it carries;
     * only a conclusion-only request gets the looser check.
     */
    public function authorize(): Response
    {
        return $this->touchesPlan()
            ? $this->inspectPlaytest('update')
            : $this->inspectPlaytest('recordConclusion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Every field is `sometimes`, which is what lets the command tell "left
     * this alone" from "cleared this". Without that distinction a request
     * meaning only to write a conclusion would be indistinguishable from one
     * that also blanked the hypothesis.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'game_version_id' => $this->gameVersionRules($this->game(), required: false),
            'title' => $this->playtestTitleRules(required: false),
            'objective' => $this->playtestObjectiveRules(required: false),
            'hypothesis' => $this->playtestHypothesisRules(),
            'conclusion' => $this->playtestConclusionRules(),
            'planned_at' => $this->plannedAtRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdatePlaytestData
    {
        return UpdatePlaytestData::fromArray($this->validated());
    }

    /**
     * Determine whether the request tries to rewrite what was under test.
     *
     * Read from the raw input rather than from the validated set, because the
     * ability has to be decided before validation runs.
     */
    private function touchesPlan(): bool
    {
        return array_intersect(UpdatePlaytestData::PLAN_FIELDS, array_keys($this->all())) !== [];
    }
}
