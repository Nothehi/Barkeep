<?php

namespace Modules\PrototypeIteration\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * What this module knows about a playtest: the little it needs, and no copy.
 *
 * The boundary between PrototypeIteration and Playtesting, expressed as a type.
 * An iteration screen has to show enough for somebody to recognise the evidence
 * it was judged on — "Playtest #28, completed, four players, 72 minutes, eight
 * observations" — and this is that much and not one field more. The
 * observations themselves are not here, the feedback is not here, and the
 * participants are not here, because Playtesting owns all three.
 *
 * Nothing on this object is persisted. It is built on read by the Playtesting
 * adapter from that module's own queries, so the numbers on an iteration screen
 * are the numbers Playtesting would give for the same playtest at the same
 * moment. The alternative — caching a session count on the join row — is a
 * second copy that goes stale the first time somebody adds a session, and then
 * quietly disagrees with the playtest's own screen forever.
 *
 * The link id travels alongside the playtest's own id because the interface
 * needs both: one to address the playtest, and one to detach it from this
 * iteration without naming the playtest at all.
 */
final readonly class PlaytestReference
{
    public function __construct(
        public string $linkId,
        public string $playtestId,
        public string $title,
        public string $status,
        public string $statusLabel,
        public ?DateTimeImmutable $attachedAt = null,
        public int $sessionCount = 0,
        public int $participantCount = 0,
        public int $observationCount = 0,
        public int $feedbackCount = 0,
        public ?int $totalDurationSeconds = null,
    ) {}

    /**
     * The reference for a link whose playtest can no longer be read.
     *
     * A link may outlive the caller's ability to see what it points at — the
     * commonest case being a reader who can see the iteration through one
     * grant and the playtest through none. Rendering a placeholder is more
     * honest than dropping the row, because "this iteration cited evidence you
     * cannot see" is true and useful, while a silently shorter list reads as
     * "this iteration cited nothing".
     */
    public static function unavailable(string $linkId, string $playtestId): self
    {
        return new self(
            linkId: $linkId,
            playtestId: $playtestId,
            title: __('Playtest unavailable'),
            status: 'unknown',
            statusLabel: __('Unavailable'),
        );
    }

    /**
     * Determine whether the playtest behind this reference could be read.
     */
    public function isAvailable(): bool
    {
        return $this->status !== 'unknown';
    }

    /**
     * Determine whether the playtest produced anything to reason from.
     */
    public function hasEvidence(): bool
    {
        return $this->observationCount > 0 || $this->feedbackCount > 0;
    }
}
