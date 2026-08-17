<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<GameFramework>
 */
class GameFrameworkFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<GameFramework>
     */
    protected $model = GameFramework::class;

    /**
     * Define the model's default state.
     *
     * The version is built published, which is the factory honouring the rule
     * `AssignFrameworkToGame` enforces: only a published version may be adopted. A default
     * that made a draft would hand every test an adoption the application layer would have
     * refused to create, and tests built on impossible data prove nothing.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'framework_version_id' => FrameworkVersion::factory()->published(),
            'status' => GameFrameworkStatus::Active,
            'started_at' => now(),
            'completed_at' => null,
            'adopted_by' => User::factory(),
        ];
    }

    /**
     * Adopt a framework for a specific game.
     */
    public function forGame(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->id,
            'adopted_by' => $game->created_by,
        ]);
    }

    /**
     * Follow a specific edition.
     */
    public function following(FrameworkVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'framework_version_id' => $version->id,
        ]);
    }

    /**
     * Attribute the adoption to a specific account.
     */
    public function adoptedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'adopted_by' => $user->id,
        ]);
    }

    /**
     * Put the adoption at a specific point in its lifecycle.
     */
    public function withStatus(GameFrameworkStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'completed_at' => $status === GameFrameworkStatus::Completed ? now() : null,
        ]);
    }

    /**
     * Indicate that the studio has stepped away for a while.
     */
    public function paused(): static
    {
        return $this->withStatus(GameFrameworkStatus::Paused);
    }

    /**
     * Indicate that the studio considers itself finished with the methodology.
     */
    public function completed(): static
    {
        return $this->withStatus(GameFrameworkStatus::Completed);
    }
}
