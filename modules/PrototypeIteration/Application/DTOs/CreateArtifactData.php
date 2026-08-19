<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;

/**
 * The validated input required to attach a file to a prototype version.
 *
 * The upload itself travels on the DTO rather than being read from the request
 * inside the command, so the command takes an argument rather than reaching for
 * request state — which is what lets an artifact be recorded by something that is
 * not an HTTP request at all.
 *
 * Both the name and the kind are optional and both are derived when absent: the
 * name from the uploaded filename, the kind from the reported mime type. That is
 * deliberate friction removal on the commonest action in this part of the module
 * — somebody dragging four print sheets in should not have to name and classify
 * each one — and it is safe because neither derived value is trusted for
 * anything: the name is a label and the kind is a heading in a list.
 */
final readonly class CreateArtifactData
{
    public function __construct(
        public UploadedFile $file,
        public ?string $name = null,
        public ?PrototypeArtifactType $type = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $file = $input['file'];
        $type = IterationInput::identifier($input, 'type');

        return new self(
            file: $file instanceof UploadedFile ? $file : throw new \InvalidArgumentException('An artifact needs an uploaded file.'),
            name: IterationInput::text($input, 'name'),
            type: $type === null ? null : PrototypeArtifactType::tryFrom($type),
        );
    }

    /**
     * The name to file the artifact under.
     *
     * Falls back to the uploaded filename, trimmed to its basename so nothing
     * that came from a client's filesystem ends up in a label. A file with no
     * usable name at all becomes a generic one rather than an empty row.
     */
    public function resolvedName(): string
    {
        if ($this->name !== null) {
            return $this->name;
        }

        $original = basename($this->file->getClientOriginalName());

        return $original === '' ? __('Untitled artifact') : mb_substr($original, 0, 255);
    }

    /**
     * The heading to file the artifact under.
     *
     * Guessed from the reported mime type when nobody chose. The guess is a
     * convenience rather than a control — see the enum — so a wrong one is a
     * tidiness problem and never a security one.
     */
    public function resolvedType(): PrototypeArtifactType
    {
        return $this->type ?? PrototypeArtifactType::fromMimeType($this->file->getClientMimeType());
    }
}
