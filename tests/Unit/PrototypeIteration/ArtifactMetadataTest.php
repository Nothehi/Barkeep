<?php

use Modules\PrototypeIteration\Domain\ValueObjects\ArtifactMetadata;

it('records nothing for an artifact nobody described', function () {
    $metadata = ArtifactMetadata::unknown();

    expect($metadata->isEmpty())->toBeTrue()
        ->and($metadata->size)->toBeNull()
        ->and($metadata->sizeLabel())->toBeNull();
});

it('drops nulls rather than storing them', function () {
    $metadata = new ArtifactMetadata(size: 2048, mimeType: null, originalFilename: null);

    expect($metadata->toArray())->toBe(['size' => 2048]);
});

it('round-trips what was recorded', function () {
    $metadata = new ArtifactMetadata(size: 4096, mimeType: 'application/pdf', originalFilename: 'sheet.pdf');

    expect(ArtifactMetadata::fromArray($metadata->toArray()))->toEqual($metadata);
});

/**
 * These rows are read back from JSON written by earlier versions of the module and by fixtures, so a
 * malformed key becomes null rather than an error — a list of artifacts should not fail to render because
 * one of them recorded its size as a word.
 */
it('reads a malformed stored value as unknown rather than raising', function (array $stored) {
    $metadata = ArtifactMetadata::fromArray($stored);

    expect($metadata->size)->toBeNull();
})->with([
    'a word' => [['size' => 'quite big']],
    'an array' => [['size' => [1, 2]]],
    'a null' => [['size' => null]],
    'absent' => [[]],
]);

it('ignores an empty string where a value was expected', function () {
    $metadata = ArtifactMetadata::fromArray(['mime_type' => '', 'original_filename' => '']);

    expect($metadata->mimeType)->toBeNull()
        ->and($metadata->originalFilename)->toBeNull();
});

it('reads a numeric string size, because JSON is not fussy about it', function () {
    expect(ArtifactMetadata::fromArray(['size' => '2048'])->size)->toBe(2048);
});

it('says a size the way an operating system does', function (int $bytes, string $expected) {
    expect((new ArtifactMetadata(size: $bytes))->sizeLabel())->toBe($expected);
})->with([
    'bytes' => [512, '512 B'],
    'kilobytes' => [2048, '2.0 KB'],
    'megabytes' => [5 * 1024 * 1024, '5.0 MB'],
    'gigabytes' => [3 * 1024 * 1024 * 1024, '3.0 GB'],
]);

/**
 * A list that shows nothing is telling the truth; one that shows "0 B" for an unknown size is not.
 */
it('reports no size label at all when the size is unknown', function () {
    expect((new ArtifactMetadata)->sizeLabel())->toBeNull();
});

it('reports zero bytes as zero, which is different from unknown', function () {
    expect((new ArtifactMetadata(size: 0))->sizeLabel())->toBe('0 B');
});
