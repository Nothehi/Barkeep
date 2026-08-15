<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Game>
     */
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'workspace_id' => Workspace::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'status' => GameStatus::Draft,
            'design_phase' => DesignPhase::Idea,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Put the game in a specific workspace.
     *
     * Also names the workspace's owner as the creator by default, so a game
     * built this way has a creator who can actually see it — the state the
     * application layer always produces.
     */
    public function inWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
            'created_by' => $workspace->owner_id,
        ]);
    }

    /**
     * Attribute the game to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Give the game a specific address.
     */
    public function withSlug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => $slug,
        ]);
    }

    /**
     * Give the game a specific name, and an address derived from it.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Put the game at a specific point in its project lifecycle.
     */
    public function withStatus(GameStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Put the game at a specific point in the design process.
     */
    public function inPhase(DesignPhase $phase): static
    {
        return $this->state(fn (array $attributes) => [
            'design_phase' => $phase,
        ]);
    }

    /**
     * Indicate that work on the game has started.
     */
    public function active(): static
    {
        return $this->withStatus(GameStatus::Active);
    }

    /**
     * Indicate that the game has been parked.
     */
    public function onHold(): static
    {
        return $this->withStatus(GameStatus::OnHold);
    }

    /**
     * Indicate that the design is finished.
     */
    public function completed(): static
    {
        return $this->withStatus(GameStatus::Completed);
    }

    /**
     * Indicate that the game has been put away and is read-only.
     */
    public function archived(): static
    {
        return $this->withStatus(GameStatus::Archived);
    }
}
