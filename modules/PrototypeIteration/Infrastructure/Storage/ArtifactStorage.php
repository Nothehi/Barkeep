<?php

namespace Modules\PrototypeIteration\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\ValueObjects\ArtifactMetadata;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Where a prototype's files live, and the only place this module touches a disk.
 *
 * A thin adapter over the application's configured filesystem rather than a
 * file-handling implementation of its own. Nothing here opens a stream, builds a
 * path by concatenation in a controller, or decides what a URL should look like —
 * the framework's filesystem contract already does all of that, across local
 * disks and object storage alike, and reimplementing any of it would mean the
 * module had to be revisited the day the deployment changed.
 *
 * Deliberately not an asset management system. There are no folders, no
 * thumbnails, no derived renditions, no revisions of a single file. An artifact
 * is a row and a blob; that is section 9's instruction and it is also the right
 * amount of machinery for a print sheet somebody wants to find again next month.
 *
 * ## The two properties worth knowing
 *
 * **Stored names are generated, never taken from the upload.** The reference is a
 * uuid plus a normalised extension, so a file called `../../.env` or
 * `report.php` becomes `prototypes/…/9f0c….php` under a path this class built.
 * The original name is kept in metadata, offered back on download, and never used
 * to address anything.
 *
 * **Nothing is public.** Files are written to the application's default disk
 * under a private prefix and served back through a route that authorizes first —
 * there is no public URL for an artifact, because an unlisted URL is not an
 * access control and a studio's unreleased card art is exactly the thing that
 * must not leak by being guessable.
 */
final class ArtifactStorage
{
    /**
     * The prefix every artifact is written under.
     *
     * One root for the module, so a deployment can point it at a different disk
     * or lifecycle-rule it wholesale without knowing anything about the schema.
     */
    private const ROOT = 'prototype-artifacts';

    public function __construct(private readonly FilesystemFactory $filesystems) {}

    /**
     * Put an uploaded file on the disk and describe what was stored.
     *
     * Returns the reference and the metadata together because they are written to
     * the same row and neither is useful without the other — handing back only a
     * path would leave the caller re-reading the upload for its size.
     *
     * The directory carries the prototype and version ids so that a human
     * looking at the disk during an incident can tell what they are looking at.
     * Nothing reads them back: the row is the authority on where its file is.
     *
     * @return array{reference: string, metadata: ArtifactMetadata}
     */
    public function store(PrototypeVersion $version, UploadedFile $file): array
    {
        $directory = sprintf(
            '%s/%s/%s',
            self::ROOT,
            $version->prototype_id,
            $version->getKey(),
        );

        /*
         * The stored name is ours end to end: a uuid, plus the extension the
         * upload claimed reduced to lowercase letters and digits. That is what
         * makes a hostile filename a non-event rather than something to sanitise
         * — there is nothing of the client's in the path at all.
         */
        $reference = $this->disk()->putFileAs(
            $directory,
            $file,
            $this->storedName($file),
        );

        return [
            'reference' => (string) $reference,
            'metadata' => new ArtifactMetadata(
                size: $this->sizeOf($file),
                mimeType: $file->getClientMimeType(),
                originalFilename: $this->originalNameOf($file),
            ),
        ];
    }

    /**
     * Remove an artifact's file from the disk.
     *
     * Missing is treated as removed rather than as an error. The row is the
     * record and the file is a consequence of it, so a delete that finds nothing
     * has reached the state the caller wanted — and refusing would leave a
     * studio unable to tidy up a row whose file went missing in a restore.
     */
    public function delete(PrototypeArtifact $artifact): void
    {
        $reference = $artifact->storage_reference;

        if ($reference === '') {
            return;
        }

        $this->disk()->delete($reference);
    }

    /**
     * Remove a file by its stored reference, before any row points at it.
     *
     * The rollback path for an upload whose row failed to write. It takes a bare
     * reference rather than an artifact because at that moment there is no artifact
     * — the bytes are on the disk and nothing in the database mentions them, which
     * is precisely the state this exists to clean up.
     *
     * Failures are swallowed on purpose, and this is the only place in the module
     * that swallows one. The caller is already unwinding a failed write and is about
     * to re-raise the real error; a secondary exception from the tidy-up would
     * replace a useful message ("the database rejected this") with a useless one
     * ("could not delete a temporary file"), and the leftover it would be reporting
     * is unreachable bytes that the disk's own lifecycle rules will collect.
     */
    public function deleteReference(string $reference): void
    {
        if ($reference === '') {
            return;
        }

        try {
            $this->disk()->delete($reference);
        } catch (Throwable) {
            // The upload is already failing; see the note above.
        }
    }

    /**
     * Determine whether an artifact's file is actually there.
     *
     * Surfaced so a list can mark a row whose file has gone missing rather than
     * offering a download that will fail. Cheap on a local disk and one HEAD on
     * object storage, which is worth it on a detail screen and is why nothing
     * calls it per row in a long list.
     */
    public function exists(PrototypeArtifact $artifact): bool
    {
        return $artifact->storage_reference !== ''
            && $this->disk()->exists($artifact->storage_reference);
    }

    /**
     * Stream an artifact back to the caller under its original name.
     *
     * A streamed response rather than a redirect to a signed URL. The artifact is
     * behind a policy, and a redirect would hand out a credential that outlives
     * the check — for a studio's unreleased card art that is precisely the wrong
     * trade. Streaming also means the download works identically whatever disk is
     * configured.
     *
     * The name offered is the one the uploader used, falling back to the
     * artifact's own name, so somebody who uploaded `cards-v3.pdf` gets
     * `cards-v3.pdf` back rather than a uuid.
     */
    public function download(PrototypeArtifact $artifact): StreamedResponse
    {
        return $this->disk()->download(
            $artifact->storage_reference,
            $this->downloadNameFor($artifact),
        );
    }

    /**
     * The disk artifacts are written to.
     *
     * The application's configured default, so a deployment moving from local
     * storage to object storage changes a config value and nothing here.
     */
    private function disk(): Filesystem
    {
        return $this->filesystems->disk();
    }

    /**
     * The name a file is stored under: a uuid and a safe extension.
     */
    private function storedName(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?? '';

        return $extension === ''
            ? (string) Str::uuid()
            : Str::uuid().'.'.substr($extension, 0, 16);
    }

    /**
     * The size the upload reported, or null when it could not be read.
     *
     * A failed `getSize()` returns false on some transports, and storing that as
     * zero would put a wrong fact in the record — an unknown size has to stay
     * distinguishable from an empty file.
     */
    private function sizeOf(UploadedFile $file): ?int
    {
        $size = $file->getSize();

        return is_int($size) ? $size : null;
    }

    /**
     * The name the uploader's own machine used, kept for display only.
     *
     * Trimmed to its basename before it is stored, so nothing further down the
     * line — a download header, a log line, an export — is ever handed a path
     * fragment that came from a client.
     */
    private function originalNameOf(UploadedFile $file): ?string
    {
        $name = basename($file->getClientOriginalName());

        return $name === '' ? null : mb_substr($name, 0, 255);
    }

    /**
     * The filename a download is offered under.
     */
    private function downloadNameFor(PrototypeArtifact $artifact): string
    {
        return $artifact->metadata()->originalFilename ?? $artifact->name;
    }
}
