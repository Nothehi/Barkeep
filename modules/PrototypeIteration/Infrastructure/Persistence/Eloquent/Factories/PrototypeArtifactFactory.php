<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\ValueObjects\ArtifactMetadata;

/**
 * @extends Factory<PrototypeArtifact>
 */
class PrototypeArtifactFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PrototypeArtifact>
     */
    protected $model = PrototypeArtifact::class;

    /**
     * Define the model's default state.
     *
     * The reference is a plausible path rather than a real file. Nothing that
     * reads an artifact row needs the bytes to exist — a list, a resource and a
     * policy all work from the row alone — and a factory that wrote to a disk
     * would make every test that touched a prototype slower and leave rubbish
     * behind. Tests that exercise the file itself fake the disk and upload
     * through the command, which is the path that actually writes one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prototype_version_id' => PrototypeVersion::factory(),
            'name' => rtrim(fake()->sentence(2), '.').'.pdf',
            'type' => PrototypeArtifactType::Pdf,
            'storage_reference' => 'prototype-artifacts/'.Str::uuid().'/'.Str::uuid().'.pdf',
            'metadata' => (new ArtifactMetadata(
                size: fake()->numberBetween(10_000, 8_000_000),
                mimeType: 'application/pdf',
                originalFilename: 'print-sheet.pdf',
            ))->toArray(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Attach the artifact to a specific prototype state.
     */
    public function forVersion(PrototypeVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'prototype_version_id' => $version->id,
            'created_by' => $version->created_by,
        ]);
    }

    /**
     * Make it a particular kind of file.
     */
    public function ofType(PrototypeArtifactType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Record nothing about the file, the way a hand-entered artifact does.
     */
    public function withoutMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => null,
        ]);
    }

    /**
     * Attribute the artifact to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
