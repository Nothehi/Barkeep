<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Infrastructure\Storage\ArtifactStorage;

/**
 * Remove a file from a state of a prototype.
 *
 * The one genuinely destructive operation in the module, and it is allowed because
 * an artifact is documentation rather than a decision: somebody uploaded the wrong
 * PDF, or a duplicate, and there is no design reasoning attached to it that deleting
 * would rewrite. Everything that *is* reasoning — iterations, changes, decisions —
 * has no delete at all.
 *
 * The row goes first and the file second, which is the reverse of the order on
 * upload and for the same reason: it prefers the harmless leftover. If the disk
 * removal fails after the row is gone, what remains is unreachable bytes; if the
 * file were removed first and the row deletion failed, what would remain is a row
 * offering a download that cannot work.
 *
 * The disk removal is deliberately outside the transaction. Rolling back a database
 * transaction cannot un-delete a file, so holding the transaction open across the
 * disk call would buy nothing and would keep a row locked for the duration of a
 * network call to object storage.
 */
final class DeletePrototypeArtifact
{
    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly ArtifactStorage $storage,
    ) {}

    public function handle(User $actor, PrototypeArtifact $artifact): void
    {
        $version = $artifact->prototypeVersion;

        if ($version !== null) {
            $this->guard->ensurePrototypeVersionAcceptsArtifacts($version);
        }

        DB::transaction(fn () => $artifact->delete());

        $this->storage->delete($artifact);
    }
}
