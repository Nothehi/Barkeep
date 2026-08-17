<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\Validation\MechanicValidationRules;
use Modules\GameDesign\Domain\Models\Mechanic;

class CreateMechanicRequest extends MechanicRequest
{
    use MechanicValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Asked against the class rather than an instance, because there is nothing
     * to ask about yet.
     */
    public function authorize(): Response
    {
        return $this->inspect('create', [Mechanic::class]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->mechanicRules();
    }
}
