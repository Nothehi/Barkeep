<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * What kind of file an artifact is.
 *
 * Coarse on purpose. This is not a mime type — the mime type is stored beside
 * the file in the artifact's metadata, where it belongs. This is the answer to
 * "what am I looking at in this list?", and a list that distinguishes
 * `image/png` from `image/jpeg` answers that worse than one that says "image".
 *
 * Seven cases, chosen so that a print-and-play prototype's whole asset set —
 * card fronts, a rulebook draft, a cost spreadsheet, a printed insert model —
 * files itself without anybody having to think.
 */
enum PrototypeArtifactType: string
{
    case Image = 'image';
    case Pdf = 'pdf';
    case Document = 'document';
    case Spreadsheet = 'spreadsheet';
    case Model = 'model';
    case Build = 'build';
    case Other = 'other';

    /**
     * The kind an artifact falls into when nobody chose one.
     */
    public static function default(): self
    {
        return self::Other;
    }

    /**
     * A human readable label for the kind.
     */
    public function label(): string
    {
        return match ($this) {
            self::Image => __('Image'),
            self::Pdf => __('PDF'),
            self::Document => __('Document'),
            self::Spreadsheet => __('Spreadsheet'),
            self::Model => __('3D model'),
            self::Build => __('Build'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of thing that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::Image => __('A card face, a board mock-up, a photo of the table.'),
            self::Pdf => __('A print sheet or a rulebook draft.'),
            self::Document => __('Written rules, notes or a design brief.'),
            self::Spreadsheet => __('A cost curve, a balance model, a card list.'),
            self::Model => __('A part to print or cut.'),
            self::Build => __('An exported playable build of a digital prototype.'),
            self::Other => __('Anything that does not fit the kinds above.'),
        };
    }

    /**
     * Guess the kind from a file's reported mime type.
     *
     * A convenience for the upload path, not a security control: the mime type
     * comes from the client, so this only decides which heading the file is
     * filed under. Nothing is served back from the guess, and an artifact
     * filed wrongly is a tidiness problem rather than a vulnerability.
     *
     * Falls through to {@see Other} rather than guessing harder. A wrong
     * confident answer is worse than "other" in a list somebody is scanning.
     */
    public static function fromMimeType(?string $mimeType): self
    {
        if ($mimeType === null || $mimeType === '') {
            return self::default();
        }

        return match (true) {
            str_starts_with($mimeType, 'image/') => self::Image,
            $mimeType === 'application/pdf' => self::Pdf,
            str_contains($mimeType, 'spreadsheet'), str_contains($mimeType, 'excel'), $mimeType === 'text/csv' => self::Spreadsheet,
            str_contains($mimeType, 'word'), str_starts_with($mimeType, 'text/') => self::Document,
            str_contains($mimeType, 'model/'), str_contains($mimeType, 'stl') => self::Model,
            str_contains($mimeType, 'zip'), str_contains($mimeType, 'octet-stream') => self::Build,
            default => self::Other,
        };
    }
}
