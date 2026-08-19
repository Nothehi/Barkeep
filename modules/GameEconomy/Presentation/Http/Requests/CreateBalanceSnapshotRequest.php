<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceSnapshotData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateBalanceSnapshotRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `createSnapshot` rather than `configure`, and the difference matters: an
     * archived profile refuses configuration and still permits a snapshot.
     * "Keep a copy of what we shipped" is a reason to take one, not a reason to
     * refuse.
     */
    public function authorize(): Response
    {
        return $this->inspectProfile('createSnapshot');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Only labels. What a snapshot contains is read from the live tables by the
     * command — a snapshot whose contents a caller could choose would not be a
     * record of anything.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->snapshotNameRules(),
            'description' => $this->descriptionRules(2000),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceSnapshotData
    {
        return BalanceSnapshotData::fromArray($this->validated());
    }
}
