<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Accepting, rejecting or deferring a decision.
 *
 * One request for the three settlements, distinguished by which route reached it rather than by a
 * value in the body. That is the point: "accept" is something a designer does, and a
 * `PATCH {status: "accepted"}` would make the studio's recorded intention an editable field —
 * which is precisely what the lifecycle exists to prevent, since accepted and rejected are
 * terminal.
 */
class SettleDecisionRequest extends PrototypeIterationRequest
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
