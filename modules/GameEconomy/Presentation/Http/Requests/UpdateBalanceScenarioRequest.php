<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceScenarioData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;

class UpdateBalanceScenarioRequest extends BalanceRequest
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
     * The status is accepted here, unlike a profile's — and the difference is
     * that it means something different. A design state has one active profile,
     * so activating one retires another and is genuinely an action; any number
     * of scenarios may be active at once, so this is closer to a flag.
     *
     * Archiving still has its own endpoint, because that one cannot be undone.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->scenarioNameRules(required: false),
            'description' => $this->descriptionRules(),
            'status' => $this->scenarioStatusRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceScenarioData
    {
        return BalanceScenarioData::fromArray($this->validated());
    }

    /**
     * The lifecycle state the request asked for, if it asked for one.
     */
    public function toStatus(): ?BalanceScenarioStatus
    {
        $status = $this->validated('status');

        return is_string($status) ? BalanceScenarioStatus::tryFrom($status) : null;
    }
}
