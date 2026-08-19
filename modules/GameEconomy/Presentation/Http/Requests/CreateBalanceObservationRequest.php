<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceObservationData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateBalanceObservationRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectConfiguration();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The source reference is a plain string and is checked against nothing.
     * Resolving a playtest id here would mean this module importing Playtesting,
     * and once it can do that it ends up holding a copy of the evidence rather
     * than a pointer to it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->observationTitleRules(),
            'observation' => $this->observationBodyRules(),
            'source_type' => $this->observationSourceRules(),
            'source_reference' => $this->sourceReferenceRules(),
            'severity' => $this->severityRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceObservationData
    {
        return BalanceObservationData::fromArray($this->validated());
    }
}
