<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Disconnecting a cycle from a playtest it was not actually tested through.
 *
 * Addresses the association rather than the playtest — the route carries a link id, not a playtest
 * id — which is what lets the detach route belong entirely to this module and touch nothing
 * Playtesting owns.
 */
class DetachPlaytestRequest extends PrototypeIterationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('attachPlaytest');
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
