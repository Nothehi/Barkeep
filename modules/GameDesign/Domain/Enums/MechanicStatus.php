<?php

namespace Modules\GameDesign\Domain\Enums;

/**
 * Whether a term is part of the vocabulary a designer may pick from.
 *
 * Two states, and there is deliberately no draft. A mechanic is a word with a
 * definition; there is nothing to work up to in private, and a half-written
 * term that games could not yet claim would be a row nobody could use and
 * everybody could see.
 *
 * Archiving is the only way a term leaves. A mechanic that turns out to be a
 * duplicate, or a name the field stopped using, stops being offered — and the
 * games that already claimed it keep saying what they said. Deleting the row
 * would rewrite other people's design records, which is not a curator's to do.
 */
enum MechanicStatus: string
{
    case Published = 'published';
    case Archived = 'archived';

    /**
     * The status a newly written mechanic starts in.
     */
    public static function default(): self
    {
        return self::Published;
    }

    /**
     * Determine whether a game may newly claim this mechanic.
     *
     * False for an archived term, which is what stops the vocabulary growing
     * back through the picker after a curator has retired something.
     */
    public function allowsAdoption(): bool
    {
        return $this === self::Published;
    }

    /**
     * A human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Published => __('In the vocabulary'),
            self::Archived => __('Retired'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Published => __('This mechanic is in the vocabulary.'),
            self::Archived => __('This mechanic has been retired and cannot be added to a game.'),
        };
    }
}
