<?php

namespace Modules\Playtesting\Application\DTOs;

use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\ValueObjects\SessionDuration;

/**
 * What a playtest has produced so far, counted rather than stored.
 *
 * Every figure here is derived on read. Nothing is persisted, no rollup table
 * exists, and no listener maintains a counter — because the moment a stored
 * count and the rows it describes can disagree, somebody spends an afternoon
 * finding out which one is lying.
 *
 * That is affordable because the numbers are small. A playtest has a handful
 * of sessions and a session has a handful of people; the whole summary is a
 * few aggregate queries. When a designer eventually wants figures across
 * hundreds of playtests, that is a reporting problem for the analytics
 * capability rather than a reason to denormalise this one.
 *
 * Two absences are deliberate. Nothing here interprets the hypothesis, and
 * nothing scores the playtest: an average of four ratings is a fact, and "this
 * playtest went well" is a judgement the platform is in no position to make.
 */
final readonly class PlaytestSummary
{
    public function __construct(
        public Playtest $playtest,
        public int $sessionCount,
        public int $completedSessionCount,
        public int $cancelledSessionCount,
        public int $participantCount,
        public int $playerCount,
        public int $observationCount,
        public int $feedbackCount,
        public int $ratedFeedbackCount,
        public ?float $averageRating,
        public ?SessionDuration $totalDuration,
        public ?SessionDuration $averageSessionDuration,
    ) {}

    /**
     * The summary of a playtest nobody has run yet.
     *
     * Zeroes for the counts and nulls for the averages, which is the honest
     * shape: a playtest with no rated feedback has no average rating, and
     * reporting 0.0 would put it at the bottom of any list it appeared in.
     */
    public static function empty(Playtest $playtest): self
    {
        return new self(
            playtest: $playtest,
            sessionCount: 0,
            completedSessionCount: 0,
            cancelledSessionCount: 0,
            participantCount: 0,
            playerCount: 0,
            observationCount: 0,
            feedbackCount: 0,
            ratedFeedbackCount: 0,
            averageRating: null,
            totalDuration: null,
            averageSessionDuration: null,
        );
    }

    /**
     * Determine whether the playtest has gathered anything at all.
     *
     * The question behind "complete this playtest" being offered or not, and
     * behind whether a summary is worth drawing.
     */
    public function hasEvidence(): bool
    {
        return $this->observationCount > 0 || $this->feedbackCount > 0;
    }
}
