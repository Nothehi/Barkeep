<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * A write inside a configuration that carries no body.
 *
 * Every delete in this module — a resource, a flow, an action, a cost, a reward,
 * an effect, a variable, a scenario override — is the same request: prove the
 * caller may change the configuration this record is inside, and take nothing
 * from them.
 *
 * One class rather than eight identical ones, because the alternative is eight
 * files whose only difference is their name, and eight places for the ability to
 * be typed wrong. The record being deleted is already resolved by the router
 * *through* its profile, so which record it is has been established before this
 * runs — there is nothing left for a per-record request to check.
 *
 * Requests that do carry a body keep their own class, because they carry their
 * own rules.
 */
class ConfigurationChangeRequest extends BalanceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectConfiguration();
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
