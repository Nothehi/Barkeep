<?php

use Carbon\CarbonImmutable;
use Modules\Playtesting\Domain\ValueObjects\SessionDuration;

it('measures the span between a start and an end', function () {
    $start = CarbonImmutable::parse('2026-08-16 19:00:00');

    expect(SessionDuration::between($start, $start->addMinutes(75))?->seconds)->toBe(75 * 60);
});

/**
 * "Still running" and "never started" are absences rather than zeroes. A zero
 * would average into a playtest's figures as a session that took no time at
 * all.
 */
it('has no duration when either end is unknown', function (?string $start, ?string $end) {
    expect(SessionDuration::between(
        $start === null ? null : CarbonImmutable::parse($start),
        $end === null ? null : CarbonImmutable::parse($end),
    ))->toBeNull();
})->with([
    'still running' => ['2026-08-16 19:00:00', null],
    'never started' => [null, '2026-08-16 21:00:00'],
    'neither' => [null, null],
]);

/**
 * An end before its start means a timestamp was corrected badly somewhere. A
 * negative duration would poison every average it reached.
 */
it('refuses to report a negative duration', function () {
    $start = CarbonImmutable::parse('2026-08-16 21:00:00');

    expect(SessionDuration::between($start, $start->subHour()))->toBeNull();
});

it('reports whole minutes, rounded down', function () {
    expect(SessionDuration::fromSeconds(119)->minutes())->toBe(1);
});

it('never reports a negative span even when built from one', function () {
    expect(SessionDuration::fromSeconds(-500)->seconds)->toBe(0);
});

/**
 * Board game sessions are talked about in hours and minutes, so seconds only
 * appear when there is nothing else to show — which in practice means a
 * session somebody started and ended by accident.
 */
it('writes itself the way people talk about game length', function (int $seconds, string $expected) {
    expect(SessionDuration::fromSeconds($seconds)->label())->toBe($expected);
})->with([
    'hours and minutes' => [75 * 60, '1h 15m'],
    'whole hours' => [2 * 3600, '2h'],
    'minutes only' => [45 * 60, '45m'],
    'seconds only' => [30, '30s'],
    'nothing at all' => [0, '0s'],
    'a long evening' => [3 * 3600 + 5 * 60, '3h 5m'],
]);
