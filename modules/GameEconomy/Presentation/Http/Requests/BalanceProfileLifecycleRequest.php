<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;

/**
 * Putting a configuration into play, or putting it away.
 *
 * No body at all. Both moves are actions rather than field edits — which is what
 * keeps an irreversible one from being a value somebody could send by accident —
 * and the ability checked is the move's own, so archiving and activating can
 * come apart later without touching either controller.
 */
class BalanceProfileLifecycleRequest extends BalanceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The ability is read from the route's own name, so `.../activate` checks
     * `activate` and `.../archive` checks `archive` without a second mapping to
     * keep in step.
     */
    public function authorize(): Response
    {
        return $this->inspectProfile($this->ability());
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

    /**
     * Which lifecycle move this request is.
     */
    public function ability(): string
    {
        return str_ends_with((string) $this->route()?->getName(), 'activate') ? 'activate' : 'archive';
    }
}
