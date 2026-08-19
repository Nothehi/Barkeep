<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\CreateArtifactData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Storage\ArtifactStorage;
use Throwable;

/**
 * Attach a file to a state of a prototype.
 *
 * Two writes that have to agree: bytes onto a disk and a row into the database. They
 * cannot be made atomic — a filesystem does not join a transaction — so the order
 * and the failure handling are the design.
 *
 * The file is written first and the row second, inside a transaction that removes
 * the file again if the row fails. That ordering is chosen because of which
 * leftover is worse. A row with no file is a broken download and a list that lies;
 * a file with no row is bytes nobody can reach, invisible and eventually swept up
 * by whatever cleans the disk. So the sequence prefers the second failure, and then
 * tries to avoid even that.
 *
 * Note the deliberate non-guard: a prototype version that has already been built
 * upon still accepts artifacts. The immutability rule freezes what a version *is*,
 * and a print sheet uploaded later documents what it was rather than changing it.
 * Somebody finding last month's card fronts on their desktop should be able to file
 * them against the version they belong to.
 *
 * No event is dispatched. An artifact is a file attached to a record, not something
 * that happened to the design, and the events in this module are for design work.
 */
final class CreatePrototypeArtifact
{
    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly ArtifactStorage $storage,
    ) {}

    public function handle(User $creator, PrototypeVersion $version, CreateArtifactData $data): PrototypeArtifact
    {
        $this->guard->ensurePrototypeVersionAcceptsArtifacts($version);

        ['reference' => $reference, 'metadata' => $metadata] = $this->storage->store($version, $data->file);

        try {
            return DB::transaction(function () use ($creator, $version, $data, $reference, $metadata): PrototypeArtifact {
                $artifact = new PrototypeArtifact;

                $artifact->fill(['name' => $data->resolvedName()]);

                $artifact->prototype_version_id = $version->getKey();
                $artifact->type = $data->resolvedType();
                $artifact->storage_reference = $reference;
                $artifact->metadata = $metadata->isEmpty() ? null : $metadata->toArray();
                $artifact->created_by = $creator->id;

                $artifact->save();

                $artifact->setRelation('prototypeVersion', $version);
                $artifact->setRelation('creator', $creator);

                return $artifact;
            });
        } catch (Throwable $failure) {
            /*
             * The row did not land, so nothing points at the bytes. Take them back
             * off the disk rather than leaving an orphan, and re-raise — the caller
             * asked for an artifact and did not get one, which is not something to
             * swallow.
             */
            $this->storage->deleteReference($reference);

            throw $failure;
        }
    }
}
