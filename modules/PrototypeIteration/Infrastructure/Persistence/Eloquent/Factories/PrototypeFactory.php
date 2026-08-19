<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;
use Modules\PrototypeIteration\Domain\Models\Prototype;

/**
 * @extends Factory<Prototype>
 */
class PrototypeFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Prototype>
     */
    protected $model = Prototype::class;

    /**
     * Define the model's default state.
     *
     * The game version is built *from* the game rather than beside it, which is
     * the factory honouring the module's central invariant. A default that made
     * two unrelated records would hand every test a prototype the application
     * layer would have refused to create, and tests built on impossible data
     * prove nothing.
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
            'name' => rtrim(fake()->sentence(2), '.').' prototype',
            'description' => fake()->sentence(),
            'type' => PrototypeType::Paper,
            'status' => PrototypeStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Build for a specific game, cutting a fresh design state to build from.
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
     * Build from a specific design state, taking the game from it.
     *
     * The only way to set the game version directly, and it sets the game to
     * match — because the pair is what the module guarantees, and a factory that
     * let a caller split them would be a way to write data no command could.
     */
    public function forVersion(GameVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $version->game_id,
            'game_version_id' => $version->id,
        ]);
    }

    /**
     * Give the prototype a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Make it a particular kind of thing.
     */
    public function ofType(PrototypeType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Put the prototype at a specific point in its lifecycle.
     */
    public function withStatus(PrototypeStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that the prototype is the one being worked on.
     */
    public function active(): static
    {
        return $this->withStatus(PrototypeStatus::Active);
    }

    /**
     * Indicate that the prototype has been put away.
     */
    public function archived(): static
    {
        return $this->withStatus(PrototypeStatus::Archived);
    }

    /**
     * Attribute the prototype to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
