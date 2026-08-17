<?php

use Modules\DesignFramework\Domain\Enums\FrameworkStatus;

it('starts life as a draft', function () {
    expect(FrameworkStatus::default())->toBe(FrameworkStatus::Draft);
});

it('allows a draft to be published or abandoned', function () {
    expect(FrameworkStatus::Draft->transitions())
        ->toBe([FrameworkStatus::Published, FrameworkStatus::Archived]);
});

/**
 * The module's central invariant, expressed as an absence.
 *
 * Unpublishing would let a version's questions change underneath the answers already recorded against
 * them, which is the one failure versioning exists to prevent. The way to change a published
 * methodology is to create the next version.
 */
it('never lets a published framework return to draft', function () {
    expect(FrameworkStatus::Published->canTransitionTo(FrameworkStatus::Draft))->toBeFalse()
        ->and(FrameworkStatus::Published->transitions())->toBe([FrameworkStatus::Archived]);
});

it('treats archived as terminal', function () {
    expect(FrameworkStatus::Archived->transitions())->toBe([])
        ->and(FrameworkStatus::Archived->isTerminal())->toBeTrue();
});

it('only lets a draft be modified', function () {
    expect(FrameworkStatus::Draft->allowsModification())->toBeTrue()
        ->and(FrameworkStatus::Published->allowsModification())->toBeFalse()
        ->and(FrameworkStatus::Archived->allowsModification())->toBeFalse();
});

/**
 * Editability and adoptability are deliberately disjoint. A version whose content could change while
 * games answered its questions would defeat the point of having versions at all.
 */
it('only lets a published version be adopted', function (FrameworkStatus $status, bool $adoptable) {
    expect($status->allowsAdoption())->toBe($adoptable)
        ->and($status->allowsModification())->toBe(! $adoptable && $status === FrameworkStatus::Draft);
})->with([
    [FrameworkStatus::Draft, false],
    [FrameworkStatus::Published, true],
    [FrameworkStatus::Archived, false],
]);

it('hides drafts from designers at large', function () {
    expect(FrameworkStatus::Draft->isPubliclyVisible())->toBeFalse()
        ->and(FrameworkStatus::Published->isPubliclyVisible())->toBeTrue()
        ->and(FrameworkStatus::Archived->isPubliclyVisible())->toBeTrue();
});

it('words every status and every move it can make', function (FrameworkStatus $status) {
    expect($status->label())->not->toBe('')
        ->and($status->deniedReason())->not->toBe('');

    foreach ($status->transitions() as $target) {
        expect($status->transitionLabelTo($target))->not->toBe('');
    }
})->with(FrameworkStatus::cases());
