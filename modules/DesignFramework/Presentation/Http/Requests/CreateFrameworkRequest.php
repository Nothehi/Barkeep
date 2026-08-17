<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\CreateFrameworkData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;
use Modules\DesignFramework\Domain\Models\Framework;

class CreateFrameworkRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('create', [Framework::class]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * There is no rule for the status: every framework starts as a draft, and anything sent would
     * be ignored — so it is not accepted. The address is optional because the command derives one
     * from the name when it is absent.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->frameworkNameRules(),
            'slug' => $this->frameworkSlugRules(),
            'description' => $this->frameworkDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateFrameworkData
    {
        return CreateFrameworkData::fromArray($this->validated());
    }
}
