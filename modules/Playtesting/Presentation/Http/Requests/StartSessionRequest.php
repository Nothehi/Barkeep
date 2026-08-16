<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Beginning a session.
 *
 * No input, deliberately. The start time comes from the clock rather than from
 * the request, because it is the anchor the duration, the timeline and the
 * elapsed counter all hang off — a caller-supplied one would let any of those
 * be quietly wrong.
 */
class StartSessionRequest extends PlaytestRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('start');
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
