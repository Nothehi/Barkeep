<?php

use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;

it('files an unrecognised upload under other rather than guessing', function (?string $mimeType) {
    expect(PrototypeArtifactType::fromMimeType($mimeType))->toBe(PrototypeArtifactType::Other);
})->with([
    'nothing reported' => null,
    'an empty string' => '',
]);

it('files a recognised upload under the heading a reader would expect', function (string $mimeType, PrototypeArtifactType $expected) {
    expect(PrototypeArtifactType::fromMimeType($mimeType))->toBe($expected);
})->with([
    'png' => ['image/png', PrototypeArtifactType::Image],
    'jpeg' => ['image/jpeg', PrototypeArtifactType::Image],
    'pdf' => ['application/pdf', PrototypeArtifactType::Pdf],
    'csv' => ['text/csv', PrototypeArtifactType::Spreadsheet],
    'excel' => ['application/vnd.ms-excel', PrototypeArtifactType::Spreadsheet],
    'word' => ['application/msword', PrototypeArtifactType::Document],
    'plain text' => ['text/plain', PrototypeArtifactType::Document],
    'stl' => ['model/stl', PrototypeArtifactType::Model],
    'zip' => ['application/zip', PrototypeArtifactType::Build],
]);

/**
 * The guess is a convenience for the upload path, never a control: the mime type comes from the client, so
 * an artifact filed wrongly is a tidiness problem rather than a vulnerability.
 */
it('never fails on something it does not recognise', function () {
    expect(PrototypeArtifactType::fromMimeType('application/x-invented-by-a-client'))
        ->toBe(PrototypeArtifactType::Other);
});

it('words and explains every kind', function (PrototypeArtifactType $type) {
    expect($type->label())->not->toBeEmpty()
        ->and($type->description())->not->toBeEmpty();
})->with(PrototypeArtifactType::cases());
