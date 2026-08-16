<?php

namespace Modules\Playtesting\Application\DTOs;

use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;

/**
 * The validated input required to record what a participant said.
 *
 * The participant is optional because anonymous feedback is often the honest
 * kind: somebody who did not enjoy a friend's game says so more readily when
 * their name is not attached.
 *
 * The rating is optional because a number is not what makes feedback useful.
 * "I never knew what my best move was" tells a designer more than any score,
 * and refusing it for want of one would lose it.
 */
final readonly class CreateFeedbackData
{
    public function __construct(
        public string $content,
        public ?FeedbackRating $rating = null,
        public ?string $participantId = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            content: PlaytestInput::requiredText($input, 'content'),
            rating: FeedbackRating::fromNullable(PlaytestInput::integer($input, 'rating')),
            participantId: PlaytestInput::identifier($input, 'participant_id'),
        );
    }
}
