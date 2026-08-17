<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\CompletionData;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;
use Modules\DesignFramework\Domain\Models\GameFramework;
use RuntimeException;

/**
 * Tick — or untick — a practice or a checklist item.
 *
 * One form for both, because both are binary, both are recorded by the existence of a row, and both
 * accept the same optional note. The route decides which.
 */
class CompletionRequest extends FrameworkRequest
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
     * `completed` defaults to true when absent, which is what a plain "Mark complete" button sends.
     * Passing false is how the tick is taken back.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'completed' => $this->completedFlagRules(),
            'notes' => $this->notesRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CompletionData
    {
        return CompletionData::fromArray($this->validated());
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
