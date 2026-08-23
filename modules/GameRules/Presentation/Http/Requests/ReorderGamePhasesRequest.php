<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Putting a rule set's phases into the order play visits them.
 *
 * The one reorder in this module that changes what the rules *say* rather than
 * how they are displayed.
 *
 * Takes the whole ordered list rather than one id and a new index, which is the
 * shape a drag-and-drop actually produces and the only shape that cannot go
 * half-wrong: moving one item "to position 3" leaves every other position to be
 * inferred, and two people reordering at once would infer differently.
 */
class ReorderGamePhasesRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectEdit();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->orderRules('phase_ids');
    }

    /**
     * The ids, in the order the designer put them in.
     *
     * @return list<string>
     */
    public function orderedIds(): array
    {
        /** @var list<string> $ids */
        $ids = array_values($this->validated('phase_ids', []));

        return $ids;
    }
}
