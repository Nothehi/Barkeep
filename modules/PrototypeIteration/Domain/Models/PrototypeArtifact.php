<?php

namespace Modules\PrototypeIteration\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\ValueObjects\ArtifactMetadata;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\PrototypeArtifactFactory;

/**
 * A file that belongs to one state of a prototype.
 *
 * Print sheets, card layouts, a rulebook draft, an STL, a photo of the table,
 * an exported build. These are what make a prototype version buildable again
 * by somebody who was not there, which is the whole reason they are attached
 * to the version rather than to the prototype: last week's print sheet is the
 * sheet for last week's cards.
 *
 * Deliberately not an asset management system. There are no folders, no
 * revisions of a single file, no thumbnails, no derived renditions and no
 * sharing model. An artifact is a name, a coarse kind, whatever the upload told
 * us about the file, and a reference to where the bytes are on the
 * application's configured disk. When the platform eventually wants a real
 * asset library, this is the record it will be built from rather than the
 * thing it replaces.
 *
 * The bytes are never in the database. `storage_reference` is a path on a disk,
 * resolved through the storage adapter, so the same row keeps working when
 * local storage becomes object storage.
 *
 * @property string $id
 * @property string $prototype_version_id
 * @property string $name
 * @property PrototypeArtifactType $type
 * @property string $storage_reference
 * @property array<string, mixed>|null $metadata
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PrototypeVersion|null $prototypeVersion
 * @property-read User|null $creator
 */
#[Fillable(['name'])]
class PrototypeArtifact extends Model
{
    /** @use HasFactory<PrototypeArtifactFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => PrototypeArtifactType::Other->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PrototypeArtifactType::class,
            'metadata' => 'array',
        ];
    }

    /**
     * The prototype state this file belongs to.
     *
     * @return BelongsTo<PrototypeVersion, $this>
     */
    public function prototypeVersion(): BelongsTo
    {
        return $this->belongsTo(PrototypeVersion::class, 'prototype_version_id');
    }

    /**
     * The account that uploaded it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * What was known about the file when it arrived.
     *
     * Read through a value object rather than off the array, so that "the
     * upload reported no size" is a null rather than a missing key every caller
     * has to remember to check. Every field in it came from the client, so it
     * describes the upload and is never trusted for a decision.
     */
    public function metadata(): ArtifactMetadata
    {
        return ArtifactMetadata::fromArray($this->metadata ?? []);
    }

    /**
     * Determine whether the artifact belongs to the given prototype version.
     */
    public function belongsToVersion(PrototypeVersion $version): bool
    {
        return $this->prototype_version_id === $version->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PrototypeArtifactFactory
    {
        return PrototypeArtifactFactory::new();
    }
}
