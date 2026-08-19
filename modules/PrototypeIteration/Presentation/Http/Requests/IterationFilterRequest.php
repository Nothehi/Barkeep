<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\IterationFilters;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Reading a game's design cycles, with optional narrowing.
 *
 * The outcome filter is the one designers actually reach for — "show me everything that failed"
 * is how somebody finds the thread of a problem that has been resisting them for months — and it
 * only ever matches completed cycles, since nothing else has an outcome.
 */
class IterationFilterRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGameForIterations('viewAny');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The prototype filter is only checked for shape, not for ownership. It narrows a list that
     * is already scoped to the game, so an id from elsewhere matches nothing rather than
     * reaching anything — and validating it against the game would spend a query to turn an
     * empty list into an error page.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->iterationFilterRules();
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toFilters(): IterationFilters
    {
        return IterationFilters::fromArray($this->validated());
    }
}
