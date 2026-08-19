<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\ValueObjects\PrototypeVersionNumber;

/**
 * @extends Factory<PrototypeVersion>
 */
class PrototypeVersionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PrototypeVersion>
     */
    protected $model = PrototypeVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prototype_id' => Prototype::factory(),
            'version_number' => PrototypeVersionNumber::FIRST,
            'name' => null,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Attach the version to a specific prototype.
     *
     * The number is left alone: a caller placing versions by hand is usually
     * testing what happens at a particular number, so guessing one for them would
     * get in the way. Use {@see nextFor()} to follow on from whatever the
     * prototype already has.
     */
    public function forPrototype(Prototype $prototype): static
    {
        return $this->state(fn (array $attributes) => [
            'prototype_id' => $prototype->id,
            'created_by' => $prototype->created_by,
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
     * Follow on from the prototype's highest existing state.
     */
    public function nextFor(Prototype $prototype): static
    {
        return $this->forPrototype($prototype)->state(fn (array $attributes) => [
            'version_number' => ($prototype->versions()->max('version_number') ?? 0) + 1,
        ]);
    }

    /**
     * Give the version a name of its own.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
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
