<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

/**
 * The one request form behind every reorder in the module.
 *
 * Phases, principles, criteria, practices, prompts, checklists and checklist items all reorder the
 * same way — one integer, validated against the same rule, authorized against the same version —
 * so there is one form rather than seven identical ones. The route decides what is being moved.
 *
 * The upper bound on the position is deliberately not here. How many siblings an item has is a fact
 * about the data rather than about the request, so `Position::within()` enforces it and reports the
 * refusal against this same field — which means the form still shows it in place.
 */
class ReorderRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Reordering is an edit, and it is refused on a published version like any other. Phase order
     * is part of the methodology a game is following: shuffling it would reorder somebody's
     * remaining work without their knowing.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('updateVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position' => $this->positionRules(),
        ];
    }

    /**
     * The position the caller asked for.
     */
    public function position(): int
    {
        return (int) $this->validated('position');
    }
}
