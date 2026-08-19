<?php

namespace Modules\PrototypeIteration\Domain\ValueObjects;

/**
 * What was known about an artifact's file when it arrived.
 *
 * Three advisory facts, and the word advisory is doing real work. Every value
 * here came from the client that performed the upload, so this describes the
 * upload rather than proving anything about the bytes on disk. Nothing decides
 * anything from it: the size is shown in a list, the original filename is
 * offered on the way back out, and the mime type is a hint for choosing which
 * heading the artifact is filed under.
 *
 * A value object rather than an array so that "the upload reported no size" is a
 * null every caller sees in the type, instead of a missing key each of them has
 * to remember to check. Absence is the normal case for an artifact recorded by
 * hand — somebody noting that a print sheet exists in a shared drive — and it
 * has to stay distinguishable from a size of zero.
 */
final readonly class ArtifactMetadata
{
    /**
     * The largest artifact the module accepts, in kilobytes.
     *
     * A domain rule rather than a validation detail, which is why it lives here rather than in the
     * rules trait: it is a statement about what an artifact may be, and the form request, the
     * TypeScript schema and the tests all need to say the same number.
     *
     * Generous on purpose. A print-ready card sheet at 300dpi genuinely runs to tens of megabytes, and
     * a limit that refused one would make the whole feature useless for the commonest case.
     */
    public const MAX_SIZE_KILOBYTES = 51200;

    public function __construct(
        public ?int $size = null,
        public ?string $mimeType = null,
        public ?string $originalFilename = null,
    ) {}

    /**
     * The metadata of an artifact nothing was recorded about.
     */
    public static function unknown(): self
    {
        return new self;
    }

    /**
     * Read the metadata back off a stored JSON column.
     *
     * Tolerant on purpose. This reads rows written by earlier versions of the
     * module and by fixtures, so a missing or malformed key becomes null rather
     * than an error — a list of artifacts should not fail to render because one
     * of them recorded its size as a string.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function fromArray(array $metadata): self
    {
        $size = $metadata['size'] ?? null;
        $mimeType = $metadata['mime_type'] ?? null;
        $originalFilename = $metadata['original_filename'] ?? null;

        return new self(
            size: is_numeric($size) ? (int) $size : null,
            mimeType: is_string($mimeType) && $mimeType !== '' ? $mimeType : null,
            originalFilename: is_string($originalFilename) && $originalFilename !== '' ? $originalFilename : null,
        );
    }

    /**
     * The shape written to the JSON column.
     *
     * Nulls are dropped rather than stored, so a row records what was known and
     * an absent key means the same thing as an explicit null without taking up
     * space saying so.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'size' => $this->size,
            'mime_type' => $this->mimeType,
            'original_filename' => $this->originalFilename,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * Determine whether anything at all was recorded.
     */
    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    /**
     * The size written the way somebody reads it, or null when unknown.
     *
     * Binary units, because these are files on a disk and a print sheet
     * somebody is about to download is measured the way their operating system
     * measures it. Returns null rather than "0 B" for an unknown size — a list
     * that shows nothing is telling the truth, and one that shows zero is not.
     */
    public function sizeLabel(): ?string
    {
        if ($this->size === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return $unit === 0
            ? sprintf('%d %s', (int) $size, $units[$unit])
            : sprintf('%.1f %s', $size, $units[$unit]);
    }
}
