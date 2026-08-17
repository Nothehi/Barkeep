<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\PhaseData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class CreatePhaseRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('updateVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * No position and no address. The position is allocated by `ContentSequencer` — a new phase is
     * appended, and moving it is an explicit reorder — and the address is derived from the name.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->phaseNameRules(),
            'description' => $this->contentDescriptionRules(),
            'status' => $this->contentStatusRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): PhaseData
    {
        return PhaseData::fromArray($this->validated());
    }
}
