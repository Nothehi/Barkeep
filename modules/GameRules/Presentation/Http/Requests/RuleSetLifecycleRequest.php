<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use RuntimeException;

/**
 * Activating or archiving a rule set.
 *
 * Both are POSTs to named actions rather than a PATCH of a status field, because
 * they are actions with rules — activating retires whichever set was in play and
 * is refused while the validator reports errors, and archiving cannot be undone —
 * rather than editable attributes.
 *
 * The ability is taken from the route's name rather than from the body, so a
 * caller cannot ask to be checked against the gentler of the two.
 */
class RuleSetLifecycleRequest extends RuleSetRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectRuleSet($this->ability());
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
     * Which move the route this request arrived on performs.
     */
    private function ability(): string
    {
        $name = (string) $this->route()?->getName();

        return match (true) {
            str_ends_with($name, 'activate') => 'activate',
            str_ends_with($name, 'archive') => 'archive',
            default => throw new RuntimeException(static::class.' was used on a route that is not a lifecycle action.'),
        };
    }
}
