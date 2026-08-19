<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Beginning the work on a planned cycle.
 *
 * No fields. The start time is the server's, not the caller's — every timestamp in this module
 * that claims something happened is written by the command that made it happen.
 */
class StartIterationRequest extends PrototypeIterationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('start');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
