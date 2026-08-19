<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use DateTimeImmutable;
use Modules\PrototypeIteration\Domain\Enums\TimelineEntryKind;

/**
 * One thing that happened during an iteration.
 *
 * Deliberately flat, and deliberately not a model. The timeline interleaves
 * changes, experiments, playtests, decisions and the cycle's own start and end —
 * five different tables and one other bounded context — so an entry that carried
 * its source record would hand the presentation layer a union type to
 * destructure, and would put Playtesting's model within reach of a resource.
 *
 * What travels instead is the six things a timeline row renders: when, what kind,
 * a heading, a body, a badge and — where the entry points at something — an id
 * the interface can link to. Everything is already worded, because the wording
 * comes from enums that live in the domain and a client that re-derived it would
 * be a second opinion waiting to go stale.
 *
 * `at` is nullable because not everything on the line has a moment. A change
 * recorded from notes after the fact has only the time it was written down, and
 * an entry with no moment sorts to the end rather than to the front — putting the
 * epilogue before the game is the one ordering mistake a timeline must not make.
 *
 * `counts` is the one field that is not already worded, and the exception is
 * deliberate. Pluralisation in this application happens on the client, through
 * `choice()` against the shared catalogue — no PHP in the platform builds ":count
 * observations" itself — so a playtest entry hands over the numbers and lets the
 * interface say them in the reader's own language.
 */
final readonly class TimelineEntry
{
    /**
     * @param  array<string, int>|null  $counts
     */
    public function __construct(
        public TimelineEntryKind $kind,
        public string $id,
        public string $title,
        public ?DateTimeImmutable $at = null,
        public ?string $body = null,
        public ?string $badge = null,
        public ?string $status = null,
        public ?string $reference = null,
        public ?array $counts = null,
    ) {}

    /**
     * Determine whether the entry has a moment on the clock.
     */
    public function isDated(): bool
    {
        return $this->at !== null;
    }

    /**
     * The value the timeline sorts by.
     *
     * Undated entries get the far future rather than the epoch, which is what
     * puts them at the end of the sequence. Returning a comparable string rather
     * than a timestamp keeps the sort total: two entries recorded in the same
     * second fall back to their kind and then their id, so the order is stable
     * across reads instead of depending on how the database felt.
     */
    public function sortKey(): string
    {
        return sprintf(
            '%s|%s|%s',
            $this->at?->format('Y-m-d H:i:s.u') ?? '9999-12-31 23:59:59.999999',
            $this->kind->value,
            $this->id,
        );
    }
}
