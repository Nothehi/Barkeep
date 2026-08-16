<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\PlaytestFilters;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Reading the playtests of a game, optionally narrowed.
 *
 * The filters can only narrow. The game is a route binding rather than an
 * input, so there is no query string that widens the scope to another
 * project's playtests.
 */
class PlaytestFilterRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('viewAny');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->playtestFilterRules();
    }

    /**
     * Get the validated query string as an application layer DTO.
     */
    public function toFilters(): PlaytestFilters
    {
        return PlaytestFilters::fromArray($this->validated());
    }
}
