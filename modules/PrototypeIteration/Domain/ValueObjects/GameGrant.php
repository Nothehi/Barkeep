<?php

namespace Modules\PrototypeIteration\Domain\ValueObjects;

/**
 * What the game a prototype or iteration belongs to permits, said in this
 * module's own words.
 *
 * PrototypeIteration sits on top of GameDesign the way Playtesting does, and it
 * takes the same approach: everything it needs to know about the project is
 * reduced to two booleans and a reason, once, in the adapter that reads
 * GameDesign's policy.
 *
 * Workspace roles are conspicuously absent, and so are game statuses. Whether
 * somebody may read a game already accounts for their membership, the
 * workspace's own state and the game's status — GameDesign works all of that
 * out, and asking the same questions again here would be a second
 * implementation of the tenancy rules that could disagree with the first.
 *
 * The two answers are not one question asked twice, and the split is what makes
 * design history durable. Reading is what keeps a two-year-old iteration
 * legible after the project is archived; writing is what recording new work
 * needs. An archived game grants the first and refuses the second, which is
 * exactly the behaviour a record of past reasoning requires.
 */
final readonly class GameGrant
{
    public function __construct(
        public bool $canRead,
        public bool $canWrite,
        public ?string $deniedReason = null,
    ) {}

    /**
     * The grant of somebody who cannot see the game at all.
     */
    public static function none(): self
    {
        return new self(canRead: false, canWrite: false);
    }

    /**
     * Determine whether the holder may see this game's prototypes and
     * iterations.
     */
    public function allowsReading(): bool
    {
        return $this->canRead;
    }

    /**
     * Determine whether the holder may record design work against this game.
     */
    public function allowsWriting(): bool
    {
        return $this->canRead && $this->canWrite;
    }
}
