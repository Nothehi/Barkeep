<?php

namespace Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;

/**
 * @extends Factory<PlaytestFeedback>
 */
class PlaytestFeedbackFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PlaytestFeedback>
     */
    protected $model = PlaytestFeedback::class;

    /**
     * Define the model's default state.
     *
     * Anonymous and unrated. Both are the honest defaults: the rating is
     * optional because a comment without a number is still feedback, and a
     * default that always produced one would let a bug in the "no rating"
     * path go unnoticed.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => PlaytestSession::factory(),
            'participant_id' => null,
            'content' => fake()->sentence(),
            'rating' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Record the feedback against a specific session.
     */
    public function forSession(PlaytestSession $session): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $session->id,
            'created_by' => $session->created_by,
        ]);
    }

    /**
     * Attribute the feedback to somebody who was there.
     */
    public function from(PlaytestParticipant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $participant->session_id,
            'participant_id' => $participant->id,
        ]);
    }

    /**
     * Put a score on the feedback.
     */
    public function rated(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }

    /**
     * Put a random score on the feedback, anywhere on the scale.
     */
    public function scored(): static
    {
        return $this->rated(fake()->numberBetween(FeedbackRating::MIN, FeedbackRating::MAX));
    }

    /**
     * Say what the participant said.
     */
    public function saying(string $content): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => $content,
        ]);
    }

    /**
     * Attribute the recording to a specific account.
     */
    public function recordedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
