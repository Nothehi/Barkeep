<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * What kind of thing a piece of supporting evidence points at.
 *
 * Kept generic on purpose, and this enum is where that restraint is visible.
 * The temptation with "a decision may cite evidence" is a polymorphic system
 * that can address any row in the platform; what is here instead is five
 * cases, a type and a nullable reference, because five cases cover every
 * citation a designer makes today and a generic addressing scheme would have
 * to be maintained against every context built later.
 *
 * The reference is deliberately weak — a type plus an id, resolved by the
 * context that owns it, with no foreign key. That is the price of not
 * duplicating the evidence, and it is the right price: an observation cited by
 * a decision lives in Playtesting, is read through Playtesting's own contract,
 * and stays the single copy. See {@see Note}, which has no reference at all,
 * for the case where the evidence is just something somebody wants to say.
 */
enum EvidenceType: string
{
    case Playtest = 'playtest';
    case Observation = 'observation';
    case Feedback = 'feedback';
    case Experiment = 'experiment';
    case Note = 'note';

    /**
     * The type a piece of evidence falls into when nobody chose one.
     */
    public static function default(): self
    {
        return self::Note;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Playtest => __('Playtest'),
            self::Observation => __('Observation'),
            self::Feedback => __('Feedback'),
            self::Experiment => __('Experiment'),
            self::Note => __('Note'),
        };
    }

    /**
     * Determine whether this type points at a record somewhere else.
     *
     * A note is the exception: it is the evidence rather than a pointer to it,
     * so it carries a description and no reference. Everything else names
     * something that lives in another context or another table, and the
     * description beside it is the reason it was cited.
     */
    public function requiresReference(): bool
    {
        return $this !== self::Note;
    }

    /**
     * Determine whether the referenced record belongs to Playtesting.
     *
     * The three kinds of playtest evidence are read back through Playtesting's
     * own contract rather than from a copy held here — which is what keeps
     * "what the players said" in one place, with one definition of who may see
     * it.
     */
    public function belongsToPlaytesting(): bool
    {
        return $this === self::Playtest || $this === self::Observation || $this === self::Feedback;
    }
}
