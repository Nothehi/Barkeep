<?php

use Modules\PrototypeIteration\Domain\Enums\EvidenceType;

it('treats a note as the evidence rather than a pointer to it', function () {
    expect(EvidenceType::Note->requiresReference())->toBeFalse();
});

it('requires a reference for everything that names another record', function (EvidenceType $type) {
    expect($type->requiresReference())->toBeTrue();
})->with([
    'playtest' => EvidenceType::Playtest,
    'observation' => EvidenceType::Observation,
    'feedback' => EvidenceType::Feedback,
    'experiment' => EvidenceType::Experiment,
]);

/**
 * Which types belong to Playtesting decides which of them are resolved through this module's adapter rather
 * than against its own tables.
 */
it('knows which kinds of evidence Playtesting owns', function () {
    expect(EvidenceType::Playtest->belongsToPlaytesting())->toBeTrue()
        ->and(EvidenceType::Observation->belongsToPlaytesting())->toBeTrue()
        ->and(EvidenceType::Feedback->belongsToPlaytesting())->toBeTrue()
        ->and(EvidenceType::Experiment->belongsToPlaytesting())->toBeFalse()
        ->and(EvidenceType::Note->belongsToPlaytesting())->toBeFalse();
});

it('falls back to a note, the one type that needs nothing else', function () {
    expect(EvidenceType::default())->toBe(EvidenceType::Note)
        ->and(EvidenceType::default()->requiresReference())->toBeFalse();
});

it('words every type', function (EvidenceType $type) {
    expect($type->label())->not->toBeEmpty();
})->with(EvidenceType::cases());
