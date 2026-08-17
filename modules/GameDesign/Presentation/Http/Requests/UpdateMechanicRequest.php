<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameDesign\Application\Validation\MechanicValidationRules;

class UpdateMechanicRequest extends MechanicRequest
{
    use MechanicValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('update', [$this->mechanic()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The same rules as creating one. A term's name and definition are the
     * whole of it, and an update is a replacement rather than a patch — which
     * is what makes clearing a definition possible by leaving it out.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->mechanicRules();
    }
}
