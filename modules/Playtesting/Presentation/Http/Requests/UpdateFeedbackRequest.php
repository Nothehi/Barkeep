<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CreateFeedbackData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Correcting a piece of feedback while the session is still open.
 *
 * Feedback is transcribed rather than typed by the person who said it, so
 * mishearing happens and correcting it — usually by reading it back to them —
 * is how it gets right.
 */
class UpdateFeedbackRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('manageFeedback');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => $this->feedbackContentRules(),
            'rating' => $this->feedbackRatingRules(),
            'participant_id' => $this->participantReferenceRules($this->playtestSession()),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateFeedbackData
    {
        return CreateFeedbackData::fromArray($this->validated());
    }
}
