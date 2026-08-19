<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

/**
 * Reading the difference between two frozen configurations.
 *
 * The two snapshots arrive as query parameters rather than as route segments,
 * because a comparison is not a resource — it is a question about two of them,
 * and `/snapshots/compare?from=…&to=…` says that where a nested path would
 * imply one snapshot owned the other.
 *
 * They are still resolved through the profile, in the controller, so a snapshot
 * id from another configuration fails exactly as it would in a URL.
 */
class CompareBalanceSnapshotsRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectProfile('view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->snapshotComparisonRules();
    }
}
