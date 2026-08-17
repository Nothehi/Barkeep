<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\PhaseData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class UpdatePhaseRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Checked against the version the phase belongs to rather than against the phase, because a
     * phase has no policy of its own: everything inside a version is governed by whether that
     * version is still a draft.
     */
    public function authorize(): Response
    {
        return $this->inspectOwningVersion('updateVersion', $this->phase()->version ?? $this->version());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->phaseNameRules(required: false),
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
