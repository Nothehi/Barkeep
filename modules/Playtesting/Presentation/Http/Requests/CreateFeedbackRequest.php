<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Playtesting\Application\DTOs\CreateFeedbackData;
use Modules\Playtesting\Application\Validation\PlaytestValidationRules;

/**
 * Recording what a participant said about a session.
 *
 * Both the participant and the rating are optional, for different reasons.
 * Anonymous feedback is often the honest kind — somebody who did not enjoy a
 * friend's game says so more readily when their name is not on it — and a
 * comment without a score is still feedback. Requiring either would lose
 * exactly the material that is hardest to collect.
 */
class CreateFeedbackRequest extends PlaytestRequest
{
    use PlaytestValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectSession('createFeedback');
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
