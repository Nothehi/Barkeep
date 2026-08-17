<?php

namespace Modules\DesignFramework\Domain\Models;

/**
 * Content whose answer can come from a game's design record.
 *
 * Two of the module's content types can be answered by a fact — a criterion and a
 * checklist item — and the rest cannot. A principle is held in mind, a practice is
 * carried out, a prompt is written about: none of them is a question about whether
 * something has been recorded, and none of them has a `satisfied_by` column.
 *
 * That is why this is an interface rather than a default on {@see PhaseContent}.
 * Putting `isAnsweredByTheDesignRecord()` on the base would let every caller ask a
 * principle a question it has no business being asked, and would answer false in a
 * way that reads like "not yet" rather than "never".
 *
 * The progress calculator and the seeder both narrow to this contract before
 * reaching for a fact, which is what keeps `$principle->satisfied_by` from being
 * expressible at all.
 */
interface AnsweredByADesignFact
{
    /**
     * Determine whether a fact of the game's design answers this.
     */
    public function isAnsweredByTheDesignRecord(): bool;

    /**
     * The fact that answers it, or null when a designer answers it themselves.
     *
     * A key into `DesignFacts` rather than a column name, so the vocabulary of
     * facts has one definition and content cannot name a field directly.
     */
    public function designFactKey(): ?string;
}
