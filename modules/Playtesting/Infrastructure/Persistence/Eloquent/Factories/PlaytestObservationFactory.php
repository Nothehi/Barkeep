<?php

namespace Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * @extends Factory<PlaytestObservation>
 */
class PlaytestObservationFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PlaytestObservation>
     */
    protected $model = PlaytestObservation::class;

    /**
     * Define the model's default state.
     *
     * Unattributed and undated, which is what an observation looks like when
     * somebody types one sentence and moves on — the common case, and the one
     * worth defaulting to.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => PlaytestSession::factory(),
            'participant_id' => null,
            'category' => fake()->randomElement(ObservationCategory::cases()),
            'content' => fake()->sentence(),
            'observed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Record the observation against a specific session.
     */
    public function forSession(PlaytestSession $session): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $session->id,
            'created_by' => $session->created_by,
        ]);
    }

    /**
     * Attribute the observation to somebody at the table.
     */
    public function about(PlaytestParticipant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $participant->session_id,
            'participant_id' => $participant->id,
        ]);
    }

    /**
     * File the observation under a specific heading.
     */
    public function inCategory(ObservationCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Say what was noticed.
     */
    public function saying(string $content): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => $content,
        ]);
    }

    /**
     * Pin the observation to a moment in the session.
     */
    public function observedAt(DateTimeInterface $moment): static
    {
        return $this->state(fn (array $attributes) => [
            'observed_at' => $moment,
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
