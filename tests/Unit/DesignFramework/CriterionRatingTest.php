<?php

use Modules\DesignFramework\Domain\Enums\CriterionRating;

it('starts unevaluated', function () {
    expect(CriterionRating::default())->toBe(CriterionRating::NotEvaluated);
});

/**
 * "Not evaluated" is not a low score, and it is not an option either. It is the state a criterion is in
 * before anybody acts, so offering it as a grade would make clearing an assessment look like making one.
 */
it('does not offer "not evaluated" as a grade', function () {
    expect(CriterionRating::grades())->toBe([
        CriterionRating::Weak,
        CriterionRating::NeedsWork,
        CriterionRating::Good,
        CriterionRating::Strong,
    ])->not->toContain(CriterionRating::NotEvaluated);
});

it('knows which ratings represent a judgement', function () {
    expect(CriterionRating::NotEvaluated->isEvaluated())->toBeFalse();

    foreach (CriterionRating::grades() as $grade) {
        expect($grade->isEvaluated())->toBeTrue();
    }
});

it('treats good and strong as satisfactory and nothing else', function () {
    expect(CriterionRating::Good->isSatisfactory())->toBeTrue()
        ->and(CriterionRating::Strong->isSatisfactory())->toBeTrue()
        ->and(CriterionRating::NeedsWork->isSatisfactory())->toBeFalse()
        ->and(CriterionRating::Weak->isSatisfactory())->toBeFalse()
        ->and(CriterionRating::NotEvaluated->isSatisfactory())->toBeFalse();
});

/**
 * The difference between "weak" and "needs work" is not self-evident, and a designer guessing at it is a
 * designer producing noise — so every grade explains what choosing it claims.
 */
it('explains what every grade claims', function (CriterionRating $rating) {
    expect($rating->label())->not->toBe('')
        ->and($rating->description())->not->toBe('');
})->with(CriterionRating::cases());
