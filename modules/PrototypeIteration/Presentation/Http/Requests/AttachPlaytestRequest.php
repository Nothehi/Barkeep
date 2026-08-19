<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Connecting a cycle to the playtest that tested it.
 *
 * The playtest arrives as an id in the body rather than as a route segment, and that is a
 * deliberate architectural choice rather than a convenience. A `{playtest}` segment would be
 * resolved by Playtesting's own route binding and would hand this module's controller a Playtest —
 * putting another context's Eloquent model into the middle of every request here. As a body field
 * it is resolved through this module's Playtesting adapter instead, which is the one file allowed
 * to know that Playtesting exists.
 */
class AttachPlaytestRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'playtest_id' => $this->playtestRules($this->game()),
        ];
    }

    /**
     * The playtest the caller wants attached.
     *
     * Returned as a string rather than a model, so nothing between here and the adapter holds one.
     */
    public function playtestId(): string
    {
        return (string) $this->validated('playtest_id');
    }
}
