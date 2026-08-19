<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\PrototypeFilters;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Reading a game's prototypes, with optional narrowing.
 *
 * The filters cannot widen the scope: the game comes from the route and is a required argument of
 * the query rather than a value here, so there is no combination of query parameters that reaches
 * another project's prototypes.
 */
class PrototypeFilterRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGameForPrototypes('viewAny');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->prototypeFilterRules();
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toFilters(): PrototypeFilters
    {
        return PrototypeFilters::fromArray($this->validated());
    }
}
