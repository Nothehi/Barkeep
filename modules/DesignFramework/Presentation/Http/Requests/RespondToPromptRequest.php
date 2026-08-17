<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\PromptResponseData;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;
use Modules\DesignFramework\Domain\Models\GameFramework;
use RuntimeException;

class RespondToPromptRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * The memoised adoption, so the request and the controller share one lookup.
     */
    private ?GameFramework $adoption = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectAdoption('recordProgress', $this->adoption());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The limit is generous on purpose. This is where a designer writes down what their game is
     * actually about, and a ceiling that cut them off mid-thought would be the wrong kind of
     * opinion for a tool whose whole point is to make them think.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'response' => $this->responseRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): PromptResponseData
    {
        return PromptResponseData::fromArray($this->validated());
    }

    /**
     * The adoption the content was resolved through.
     *
     * Resolved from the bound game rather than read off the route. The content binding already
     * looked the adoption up in order to find the criterion, practice, item or prompt at all — so
     * this repeats a cheap query rather than threading state through the router, which would mean
     * adding a route parameter that does not appear in the URI and then reasoning about how the
     * dispatcher orders positional arguments. Memoised, so the controller asking again is free.
     *
     * A game with no adoption cannot reach here: the content binding would have failed to resolve
     * and the request would have 404'd. The refusal below is therefore a wiring assertion, not a
     * user-facing path.
     */
    public function adoption(): GameFramework
    {
        return $this->adoption ??= app(GetGameFramework::class)->handle($this->game())
            ?? throw new RuntimeException(static::class.' was used on a game that follows no framework.');
    }
}
