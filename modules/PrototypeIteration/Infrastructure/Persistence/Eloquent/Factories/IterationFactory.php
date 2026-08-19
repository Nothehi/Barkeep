<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;

/**
 * @extends Factory<Iteration>
 */
class IterationFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<Iteration>
     */
    protected $model = Iteration::class;

    /**
     * Define the model's default state.
     *
     * All three references are built so that they agree — the game version from
     * the game, and the prototype version from a prototype of that same game.
     * This is the factory honouring the module's central invariant, and it is
     * worth more here than anywhere else in the platform: a default that made a
     * prototype version under an unrelated game would hand every test the exact
     * forgery the module exists to refuse.
     *
     * A test that wants that forgery builds it explicitly — see
     * tests/Feature/PrototypeIteration/IterationIntegrityTest.php — so the
     * impossible case is written down deliberately rather than arrived at by
     * accident.
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
            'prototype_version_id' => fn (array $attributes): string => PrototypeVersion::factory()
                ->create([
                    'prototype_id' => Prototype::factory()
                        ->create([
                            'game_id' => $attributes['game_id'],
                            'game_version_id' => $attributes['game_version_id'],
                            'created_by' => User::factory(),
                        ])->id,
                    'created_by' => User::factory(),
                ])->id,
            'title' => rtrim(fake()->sentence(3), '.'),
            'objective' => fake()->sentence(10),
            'hypothesis' => fake()->sentence(),
            'status' => IterationStatus::Planned,
            'outcome' => null,
            'summary' => null,
            'started_at' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Plan the cycle against a specific game, building everything it needs.
     *
     * The one-line way to get a valid iteration for a game that already exists,
     * which is what most tests want. It cuts a fresh design state and a fresh
     * prototype for that game, so the result is the shape a command would have
     * produced.
     */
    public function forGame(Game $game): static
    {
        return $this->state(function (array $attributes) use ($game): array {
            $version = GameVersion::factory()->nextFor($game)->create();

            $prototype = Prototype::factory()->forVersion($version)->create([
                'created_by' => $game->created_by,
            ]);

            $prototypeVersion = PrototypeVersion::factory()->nextFor($prototype)->create();

            return [
                'game_id' => $game->id,
                'game_version_id' => $version->id,
                'prototype_version_id' => $prototypeVersion->id,
                'created_by' => $game->created_by,
            ];
        });
    }

    /**
     * Plan the cycle against a specific prototype state.
     *
     * Takes the game *from* the prototype, and cuts the design state under that
     * same game, so the triple agrees. The only way to set the prototype version
     * directly — a factory that let a caller point an iteration at an unrelated
     * build would be a way to write data no command could.
     */
    public function forPrototypeVersion(PrototypeVersion $version): static
    {
        return $this->state(function (array $attributes) use ($version): array {
            $prototype = $version->prototype ?? $version->prototype()->sole();

            return [
                'game_id' => $prototype->game_id,
                'game_version_id' => $prototype->game_version_id,
                'prototype_version_id' => $version->id,
            ];
        });
    }

    /**
     * Work against a specific design state, keeping the game consistent.
     */
    public function againstGameVersion(GameVersion $gameVersion): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $gameVersion->game_id,
            'game_version_id' => $gameVersion->id,
        ]);
    }

    /**
     * Give the iteration a specific title.
     */
    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Put the cycle at a specific point in its lifecycle.
     *
     * The timestamps follow the status rather than being set separately, so a
     * factory-made iteration is never in a state a command could not have
     * produced — an in-progress cycle has a start time, a completed one has both,
     * and a cancelled one keeps whatever start it had.
     */
    public function withStatus(IterationStatus $status): static
    {
        return $this->state(fn (array $attributes) => match ($status) {
            IterationStatus::Planned => [
                'status' => $status,
                'started_at' => null,
                'completed_at' => null,
            ],
            IterationStatus::InProgress => [
                'status' => $status,
                'started_at' => now(),
                'completed_at' => null,
            ],
            IterationStatus::Completed => [
                'status' => $status,
                'started_at' => now()->subDay(),
                'completed_at' => now(),
                'outcome' => $attributes['outcome'] ?? IterationOutcome::Partial,
                'summary' => $attributes['summary'] ?? fake()->sentence(12),
            ],
            IterationStatus::Cancelled => [
                'status' => $status,
                'completed_at' => null,
            ],
        });
    }

    /**
     * Indicate that the work has begun.
     */
    public function inProgress(): static
    {
        return $this->withStatus(IterationStatus::InProgress);
    }

    /**
     * Indicate that the cycle closed, with an outcome and a summary.
     */
    public function completed(?IterationOutcome $outcome = null): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => $outcome ?? IterationOutcome::Partial,
        ])->withStatus(IterationStatus::Completed);
    }

    /**
     * Indicate that the cycle was called off.
     */
    public function cancelled(): static
    {
        return $this->withStatus(IterationStatus::Cancelled);
    }

    /**
     * Leave the hypothesis unstated, the way an exploratory cycle does.
     */
    public function withoutHypothesis(): static
    {
        return $this->state(fn (array $attributes) => [
            'hypothesis' => null,
        ]);
    }

    /**
     * Attribute the iteration to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
