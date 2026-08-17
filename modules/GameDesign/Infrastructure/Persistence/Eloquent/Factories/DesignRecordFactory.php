<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;

/**
 * @extends Factory<DesignRecord>
 */
class DesignRecordFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DesignRecord>
     */
    protected $model = DesignRecord::class;

    /**
     * Define the model's default state.
     *
     * Empty, which is the honest default: a record exists as soon as a designer
     * saves anything, and the state it spends most of its life in is "barely
     * filled in". A factory that populated every field would hand every test a
     * fully decided game and hide every question about what happens when a fact
     * is missing — which is most of what there is to test here.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
        ];
    }

    /**
     * Attach the record to a specific game.
     */
    public function forGame(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->id,
        ]);
    }

    /**
     * Record the game's one-sentence pitch.
     */
    public function pitched(string $pitch): static
    {
        return $this->state(fn (array $attributes) => [
            'pitch' => $pitch,
        ]);
    }

    /**
     * Decide the player count.
     */
    public function forPlayers(int $min, ?int $max = null): static
    {
        return $this->state(fn (array $attributes) => [
            'player_count_min' => $min,
            'player_count_max' => $max ?? $min,
        ]);
    }

    /**
     * Decide the playing time, in minutes.
     */
    public function lasting(int $min, ?int $max = null): static
    {
        return $this->state(fn (array $attributes) => [
            'play_time_min' => $min,
            'play_time_max' => $max ?? $min,
        ]);
    }

    /**
     * Decide the weight.
     */
    public function weighing(Complexity $complexity): static
    {
        return $this->state(fn (array $attributes) => [
            'complexity' => $complexity,
        ]);
    }

    /**
     * Name the intended audience.
     */
    public function forAudience(string $audience): static
    {
        return $this->state(fn (array $attributes) => [
            'audience' => $audience,
        ]);
    }

    /**
     * Write down the whole core loop.
     *
     * All five parts together, because that is how the framework asks about it
     * and a test setting four of them by hand is a test about something else.
     */
    public function withCoreLoop(): static
    {
        return $this->state(fn (array $attributes) => [
            'core_action' => 'Place a worker on an action space.',
            'core_cost' => 'The worker is unavailable until the round ends.',
            'core_reward' => 'The action space pays out its resource.',
            'win_condition' => 'Most victory points when the last space is claimed.',
            'failure_condition' => 'A player who cannot place a worker is out of the round.',
        ]);
    }

    /**
     * Decide everything the framework asks about.
     */
    public function decided(): static
    {
        return $this
            ->pitched('A game about building bridges before the river rises.')
            ->forPlayers(2, 4)
            ->lasting(45, 60)
            ->weighing(Complexity::Gateway)
            ->forAudience('Families who already play a few games a year.')
            ->withCoreLoop()
            ->state(fn (array $attributes) => ['target_age_min' => 10]);
    }
}
