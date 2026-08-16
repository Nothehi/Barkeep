<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Calling a playtest off.
 *
 * Nothing to validate: cancelling carries no input. The request exists so that
 * the ability is checked the same way every other action's is, through the
 * policy rather than through a `Gate::authorize` call somebody could forget to
 * write.
 */
class CancelPlaytestRequest extends PlaytestRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPlaytest('cancel');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
