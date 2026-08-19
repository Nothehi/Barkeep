<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Removing a change from an open cycle.
 *
 * Allowed only while the cycle is open, which is what the `recordWork` ability already says. Once
 * it closes the change is part of the design history, and a designer who has changed their mind
 * about the change itself records a *new* change reversing it in a later cycle — which is a truer
 * account anyway, because "we removed the trading phase and then put it back" is what happened.
 */
class DeleteDesignChangeRequest extends PrototypeIterationRequest
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
