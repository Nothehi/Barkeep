<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * What sort of thing an entry on an iteration's timeline is.
 *
 * The timeline is the module's primary interaction — section 41 — and this enum
 * is what makes it one list rather than five. A design cycle is read as a
 * sequence: the work started, three things were changed, an experiment ran, a
 * playtest happened, a decision was taken, the cycle closed. Presenting those as
 * separate panels would make the reader reassemble the order in their head,
 * which is exactly the work the record exists to save them.
 *
 * The two lifecycle kinds are here for the same reason. An iteration's start and
 * end are events on the timeline, not chrome around it: "the decision was taken
 * four days after the playtest and two hours before the cycle closed" is a fact
 * about how a studio works, and it is only visible when all of it is on one axis.
 */
enum TimelineEntryKind: string
{
    case Started = 'started';
    case Change = 'change';
    case Experiment = 'experiment';
    case Playtest = 'playtest';
    case Decision = 'decision';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * A human readable label for the kind.
     *
     * Used as the heading above each entry, which is why they read as nouns
     * rather than as verbs: the timeline says "DESIGN CHANGE" and then what it
     * was, in the shape section 41 sets out.
     */
    public function label(): string
    {
        return match ($this) {
            self::Started => __('Iteration started'),
            self::Change => __('Design change'),
            self::Experiment => __('Experiment'),
            self::Playtest => __('Playtest'),
            self::Decision => __('Decision'),
            self::Completed => __('Iteration completed'),
            self::Cancelled => __('Iteration cancelled'),
        };
    }

    /**
     * Determine whether the kind marks a boundary of the cycle rather than work
     * inside it.
     *
     * The interface draws these differently — they are the ends of the line
     * rather than points on it — and asking here keeps that judgement in one
     * place instead of in a condition on the client.
     */
    public function isLifecycle(): bool
    {
        return $this === self::Started || $this === self::Completed || $this === self::Cancelled;
    }
}
