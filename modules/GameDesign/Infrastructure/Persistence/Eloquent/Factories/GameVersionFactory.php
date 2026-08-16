<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<GameVersion>
 */
class GameVersionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<GameVersion>
     */
    protected $model = GameVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'version_number' => VersionNumber::FIRST,
            'name' => null,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Attach the version to a specific game.
     *
     * The number is left alone: a caller placing versions by hand is usually
     * testing what happens at a particular number, so guessing one for them
     * would get in the way. Use {@see nextFor()} to follow on from whatever
     * the game already has.
     */
    public function forGame(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->id,
            'created_by' => $game->created_by,
        ]);
    }

    /**
     * Give the version a specific number.
     */
    public function numbered(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'version_number' => $number,
        ]);
    }

    /**
     * Follow on from the game's highest existing version.
     */
    public function nextFor(Game $game): static
    {
        return $this->forGame($game)->state(fn (array $attributes) => [
            'version_number' => ($game->versions()->max('version_number') ?? 0) + 1,
        ]);
    }

    /**
     * Attribute the version to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
