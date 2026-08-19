<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Putting a prototype away for good.
 *
 * No fields, which is the shape of every lifecycle action in this module: the move is the URL,
 * not a value in the body. That is what keeps a status from being something a client can set,
 * and it is why archival — which cannot be undone — has an endpoint of its own rather than
 * sharing the update route.
 */
class ArchivePrototypeRequest extends PrototypeIterationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPrototype('archive');
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
