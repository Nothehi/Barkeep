<?php

use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;

it('starts life as a proposal', function () {
    expect(DecisionStatus::default())->toBe(DecisionStatus::Proposed);
});

it('allows exactly the moves a decision can make', function (DecisionStatus $from, array $expected) {
    expect($from->transitions())->toBe($expected);
})->with([
    'proposed' => [DecisionStatus::Proposed, [DecisionStatus::Accepted, DecisionStatus::Rejected, DecisionStatus::Deferred]],
    'deferred' => [DecisionStatus::Deferred, [DecisionStatus::Accepted, DecisionStatus::Rejected]],
    'accepted' => [DecisionStatus::Accepted, []],
    'rejected' => [DecisionStatus::Rejected, []],
]);

/**
 * The strictest rule in the module. Reversing an accepted decision in place would leave the design carrying
 * a change whose recorded justification now argues against it — so a change of mind is a new decision.
 */
it('refuses to reverse a settled decision in place', function (DecisionStatus $from, DecisionStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'accepted to rejected' => [DecisionStatus::Accepted, DecisionStatus::Rejected],
    'rejected to accepted' => [DecisionStatus::Rejected, DecisionStatus::Accepted],
    'accepted to proposed' => [DecisionStatus::Accepted, DecisionStatus::Proposed],
    'rejected to deferred' => [DecisionStatus::Rejected, DecisionStatus::Deferred],
]);

/**
 * The one non-terminal ending, because "we will look at this again after the convention" is a real answer.
 */
it('lets a deferred decision be taken up again', function () {
    expect(DecisionStatus::Deferred->isTerminal())->toBeFalse()
        ->and(DecisionStatus::Deferred->canTransitionTo(DecisionStatus::Accepted))->toBeTrue()
        ->and(DecisionStatus::Deferred->canTransitionTo(DecisionStatus::Rejected))->toBeTrue();
});

it('counts only agreement and refusal as settled', function () {
    expect(DecisionStatus::Accepted->isSettled())->toBeTrue()
        ->and(DecisionStatus::Rejected->isSettled())->toBeTrue()
        ->and(DecisionStatus::Proposed->isSettled())->toBeFalse()
        ->and(DecisionStatus::Deferred->isSettled())->toBeFalse();
});

it('keeps the wording editable only while the decision is open', function () {
    expect(DecisionStatus::Proposed->allowsModification())->toBeTrue()
        ->and(DecisionStatus::Deferred->allowsModification())->toBeTrue()
        ->and(DecisionStatus::Accepted->allowsModification())->toBeFalse()
        ->and(DecisionStatus::Rejected->allowsModification())->toBeFalse();
});

/**
 * A caller hitting the refusal has a real intention, so the message has to name the route to it.
 */
it('tells a caller what to do instead of reversing a settled decision', function (DecisionStatus $status) {
    expect($status->deniedReason())->toContain('new decision');
})->with([
    'accepted' => DecisionStatus::Accepted,
    'rejected' => DecisionStatus::Rejected,
]);

it('words every status and every move it can make', function (DecisionStatus $status) {
    expect($status->label())->not->toBeEmpty()
        ->and($status->deniedReason())->not->toBeEmpty();

    foreach ($status->transitions() as $target) {
        expect($status->transitionLabelTo($target))->not->toBeEmpty();
    }
})->with(DecisionStatus::cases());
