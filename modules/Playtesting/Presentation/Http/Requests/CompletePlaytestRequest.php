<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Closing a playtest, with what it concluded.
 *
 * The conclusion is optional here and writable afterwards, so a designer who
 * wants to close the investigation now and write it up at the weekend can.
 * Requiring it would mean the playtest stayed open until somebody found the
 * time, which is how a list fills up with investigations that finished months
 * ago.
 */
class CompletePlaytestRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectPlaytest('complete');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conclusion' => $this->playtestConclusionRules(),
        ];
    }

    /**
     * The conclusion the caller supplied, if any.
     */
    public function conclusion(): ?string
    {
        $conclusion = $this->validated('conclusion');

        if (! is_string($conclusion)) {
            return null;
        }

        $conclusion = trim($conclusion);

        return $conclusion === '' ? null : $conclusion;
    }
}
