<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Starting or abandoning an experiment.
 *
 * One request for the two moves that need no body, distinguished by which route reached it. The
 * move is the URL rather than a value in it — the same arrangement every lifecycle action in this
 * module uses, so a status is never something a client can set.
 */
class ExperimentLifecycleRequest extends PrototypeIterationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('recordWork');
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
