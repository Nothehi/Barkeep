<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * A write inside a rule set that carries no body.
 *
 * Every delete in this module — a rule, a mechanic, a phase, a transition, an
 * action, a requirement, a condition, a group, a membership, an effect, a
 * trigger, an outcome, a reference — is the same request: prove the caller may
 * change the rules this record is inside, and take nothing from them.
 *
 * One class rather than thirteen identical ones, because the alternative is
 * thirteen files whose only difference is their name, and thirteen places for the
 * ability to be typed wrong. The record being deleted is already resolved by the
 * router *through* its rule set, so which record it is has been established
 * before this runs — there is nothing left for a per-record request to check.
 *
 * Requests that do carry a body keep their own class, because they carry their
 * own rules.
 */
class StructureChangeRequest extends RuleSetRequest
{
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
