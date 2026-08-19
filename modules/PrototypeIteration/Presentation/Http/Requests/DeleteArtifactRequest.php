<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Removing a file from a prototype state.
 *
 * The only destructive request in the module. It is permitted because an artifact is
 * documentation rather than reasoning — the wrong PDF, a duplicate — and nothing in the design
 * record depends on it. The records that *do* carry reasoning have no delete route at all.
 */
class DeleteArtifactRequest extends PrototypeIterationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPrototype('deleteArtifact');
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
