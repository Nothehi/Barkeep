<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceObservationData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class UpdateBalanceObservationRequest extends BalanceRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->observationTitleRules(required: false),
            'observation' => $this->observationBodyRules(required: false),
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
