<?php

namespace Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * @extends Factory<PlaytestSession>
 */
class PlaytestSessionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PlaytestSession>
     */
    protected $model = PlaytestSession::class;

    /**
     * Define the model's default state.
     *
     * A planned session with no real timestamps, because that is what a
     * session is before anybody sits down. States below add the timestamps
     * that go with each later status, so a test never has to remember that a
     * completed session needs both of them.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'playtest_id' => Playtest::factory(),
            'status' => PlaytestSessionStatus::Planned,
            'planned_at' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'started_at' => null,
            'ended_at' => null,
            'location' => fake()->randomElement(['The kitchen table', 'Dice & Slice', 'The studio', null]),
            'notes' => null,
            'outcome' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Attach the session to a specific playtest.
     */
    public function forPlaytest(Playtest $playtest): static
    {
        return $this->state(fn (array $attributes) => [
            'playtest_id' => $playtest->id,
            'created_by' => $playtest->created_by,
        ]);
    }

    /**
     * Indicate that the session is under way.
     */
    public function inProgress(?DateTimeInterface $startedAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlaytestSessionStatus::InProgress,
            'started_at' => $startedAt ?? now()->subMinutes(30),
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the session ran and finished.
     *
     * Both timestamps are set together, an hour apart by default, so any test
     * that reaches for a completed session gets one with a duration — which is
     * what the summary and the metrics are about.
     */
    public function completed(int $minutes = 60): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $startedAt = now()->subMinutes($minutes);

            return [
                'status' => PlaytestSessionStatus::Completed,
                'started_at' => $startedAt,
                'ended_at' => $startedAt->copy()->addMinutes($minutes),
                'outcome' => 'The hypothesis held up.',
            ];
        });
    }

    /**
     * Indicate that the session was called off before anybody sat down.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlaytestSessionStatus::Cancelled,
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Give the session a specific place.
     */
    public function at(string $location): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => $location,
        ]);
    }

    /**
     * Attribute the session to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
