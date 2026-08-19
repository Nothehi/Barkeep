<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Calling a design cycle off.
 *
 * No fields, and in particular no outcome — which is the whole difference between this and
 * completion. A cancelled cycle did not fail; it stopped, and recording "failed" for abandoned
 * work would make the outcome column a record of the studio's calendar rather than of its
 * findings.
 */
class CancelIterationRequest extends PrototypeIterationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('cancel');
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
