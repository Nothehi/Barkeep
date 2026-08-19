<?php

namespace Modules\PrototypeIteration\Domain\ValueObjects;

use Modules\PrototypeIteration\Domain\Enums\EvidenceType;

/**
 * A citation resolved into something a reader can actually read.
 *
 * Section 45 asks that a decision show its supporting evidence — the playtest, the
 * observation that said players spent less time waiting, the piece of feedback
 * that said combat feels faster — and that clicking any of it go to the context
 * that owns it. This is the shape that carries that, and it is assembled on read
 * every time.
 *
 * That last point is the whole design. Nothing here is stored: the excerpt is read
 * live from Playtesting through this module's adapter at render time, so a
 * correction to an observation appears immediately in every decision that cited
 * it. A copy taken at citation time would leave a decision reading as supported by
 * words the observation no longer contains, which is a quieter and worse failure
 * than a citation that has plainly gone missing.
 *
 * `playtestId` is here so the interface can build the link back into Playtesting
 * without this module publishing a route for somebody else's records. An
 * observation and a piece of feedback both hang off a session that hangs off a
 * playtest, and the playtest is the addressable thing.
 */
final readonly class CitedEvidence
{
    public function __construct(
        public string $id,
        public EvidenceType $type,
        public string $typeLabel,
        public ?string $referenceId = null,
        public ?string $description = null,
        public ?string $excerpt = null,
        public ?string $attribution = null,
        public ?string $playtestId = null,
        public bool $isResolved = true,
    ) {}

    /**
     * A note, which is the evidence rather than a pointer to it.
     */
    public static function note(string $id, ?string $description): self
    {
        return new self(
            id: $id,
            type: EvidenceType::Note,
            typeLabel: EvidenceType::Note->label(),
            description: $description,
        );
    }

    /**
     * A citation whose target could not be resolved.
     *
     * Rendered as a citation that has gone missing rather than dropped from the
     * list. The reference is deliberately loose — no foreign key, so that this
     * module holds no copy of the evidence and no key into another context's
     * tables — and this is the honest consequence of that trade: the argument
     * survives, visibly short of one exhibit.
     *
     * The commonest cause is not deletion but permission: a reader who can see the
     * iteration through one grant and the playtest through none. Saying "you cannot
     * see this" beats a silently shorter list that reads as "nothing supported
     * this".
     */
    public static function unresolved(string $id, EvidenceType $type, ?string $referenceId, ?string $description): self
    {
        return new self(
            id: $id,
            type: $type,
            typeLabel: $type->label(),
            referenceId: $referenceId,
            description: $description,
            isResolved: false,
        );
    }

    /**
     * Determine whether there is anything to show beyond the type.
     */
    public function hasContent(): bool
    {
        return $this->excerpt !== null || $this->description !== null;
    }

    /**
     * Determine whether the interface can link this citation somewhere.
     */
    public function isLinkable(): bool
    {
        return $this->isResolved && $this->playtestId !== null;
    }
}
