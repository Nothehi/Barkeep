<?php

namespace Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Models\Playtest;

/**
 * @extends Factory<Playtest>
 */
class PlaytestFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Playtest>
     */
    protected $model = Playtest::class;

    /**
     * Define the model's default state.
     *
     * The version is built *from* the game rather than beside it, which is the
     * factory honouring the module's central invariant. A default that made
     * two unrelated games would hand every test a playtest that the
     * application layer would have refused to create, and tests built on
     * impossible data prove nothing.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'game_version_id' => fn (array $attributes): string => GameVersion::factory()
                ->create([
                    'game_id' => $attributes['game_id'],
                    'created_by' => User::factory(),
                ])->id,
            'title' => ucfirst(fake()->sentence(3)).' test',
            'objective' => fake()->sentence(),
            'hypothesis' => fake()->sentence(),
            'conclusion' => null,
            'status' => PlaytestStatus::Planned,
            'planned_at' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Test a specific game, cutting a fresh version of it to test.
     */
    public function forGame(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->id,
            'game_version_id' => GameVersion::factory()->nextFor($game)->create()->id,
            'created_by' => $game->created_by,
        ]);
    }

    /**
     * Test a specific version, taking the game from it.
     *
     * The only way to set the version directly, and it sets the game to match
     * — because the pair is what the module guarantees, and a factory that let
     * a caller split them would be a way to write data no command could.
     */
    public function forVersion(GameVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $version->game_id,
            'game_version_id' => $version->id,
        ]);
    }

    /**
     * Attribute the playtest to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Give the playtest a specific title.
     */
    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Put the playtest at a specific point in its lifecycle.
     */
    public function withStatus(PlaytestStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'completed_at' => $status === PlaytestStatus::Completed ? now() : null,
        ]);
    }

    /**
     * Indicate that the first session has begun.
     */
    public function inProgress(): static
    {
        return $this->withStatus(PlaytestStatus::InProgress);
    }

    /**
     * Indicate that the designer considers the question answered.
     */
    public function completed(): static
    {
        return $this->withStatus(PlaytestStatus::Completed);
    }

    /**
     * Indicate that the playtest was called off.
     */
    public function cancelled(): static
    {
        return $this->withStatus(PlaytestStatus::Cancelled);
    }

    /**
     * Leave the hypothesis unstated, the way an exploratory playtest does.
     */
    public function withoutHypothesis(): static
    {
        return $this->state(fn (array $attributes) => [
            'hypothesis' => null,
        ]);
    }
}
