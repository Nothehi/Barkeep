<?php

namespace Modules\Playtesting\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * @extends Factory<PlaytestParticipant>
 */
class PlaytestParticipantFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PlaytestParticipant>
     */
    protected $model = PlaytestParticipant::class;

    /**
     * Define the model's default state.
     *
     * A guest player with no account, because that is the overwhelmingly
     * common participant. A factory whose default was a registered user would
     * quietly make the unusual case the one every test exercised.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => PlaytestSession::factory(),
            'user_id' => null,
            'display_name' => fake()->firstName(),
            'role' => PlaytestParticipantRole::Player,
            'joined_at' => null,
            'left_at' => null,
        ];
    }

    /**
     * Seat the participant at a specific session.
     */
    public function forSession(PlaytestSession $session): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $session->id,
        ]);
    }

    /**
     * Link the participant to a Barkeep account.
     *
     * The display name follows the account by default, which is what the
     * "add from the team" path produces.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'display_name' => $user->name,
        ]);
    }

    /**
     * Name a guest with no Barkeep account.
     */
    public function guest(string $displayName): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'display_name' => $displayName,
        ]);
    }

    /**
     * Give the participant a specific role at the table.
     */
    public function withRole(PlaytestParticipantRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    /**
     * Indicate that the participant watched rather than played.
     */
    public function observer(): static
    {
        return $this->withRole(PlaytestParticipantRole::Observer);
    }

    /**
     * Indicate that the participant ran the session.
     */
    public function facilitator(): static
    {
        return $this->withRole(PlaytestParticipantRole::Facilitator);
    }

    /**
     * Indicate that the participant designed the game.
     */
    public function designer(): static
    {
        return $this->withRole(PlaytestParticipantRole::Designer);
    }

    /**
     * Indicate that the participant left before the end.
     */
    public function leftEarly(): static
    {
        return $this->state(fn (array $attributes) => [
            'joined_at' => now()->subHour(),
            'left_at' => now()->subMinutes(20),
        ]);
    }
}
