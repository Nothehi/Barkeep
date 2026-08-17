<?php

namespace Modules\DesignFramework\Domain\ValueObjects;

/**
 * What the game an adoption belongs to permits, said in this module's own words.
 *
 * DesignFramework sits on top of GameDesign, and it takes the same approach
 * Playtesting does: everything it needs to know about the game is reduced to two
 * booleans and a reason, once, in the adapter that reads GameDesign's policy.
 *
 * Workspace roles are conspicuously absent, and so are game statuses. Whether
 * somebody may read a game already accounts for their membership, the workspace's
 * own state and the game's status — GameDesign works all of that out, and asking
 * the same questions again here would be a second implementation of the tenancy
 * rules that could disagree with the first.
 *
 * The two answers are not the same question asked twice. Reading is what keeps a
 * studio's framework work legible after a game is archived; writing is what
 * recording new evaluations and completions needs. An archived game grants the
 * first and refuses the second, which is exactly right: the assessment of a
 * shelved design should still be readable, and nothing new should land against it.
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
     * Determine whether the holder may see this game's framework work.
     */
    public function allowsReading(): bool
    {
        return $this->canRead;
    }

    /**
     * Determine whether the holder may record framework work against this game.
     */
    public function allowsWriting(): bool
    {
        return $this->canRead && $this->canWrite;
    }
}
