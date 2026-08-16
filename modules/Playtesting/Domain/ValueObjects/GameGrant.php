<?php

namespace Modules\Playtesting\Domain\ValueObjects;

/**
 * What the game a playtest belongs to permits, said in Playtesting's own words.
 *
 * Playtesting sits on top of GameDesign the way GameDesign sits on top of
 * Workspace, and it takes the same approach: everything it needs to know about
 * the game under test is reduced to two booleans and a reason, once, in the
 * adapter that reads GameDesign's policy.
 *
 * Workspace roles are conspicuously absent, and so are game statuses. Whether
 * somebody may read a game already accounts for their membership, the
 * workspace's own state and the game's status — GameDesign works all of that
 * out, and Playtesting asking the same questions again would be a second
 * implementation of the tenancy rules that could disagree with the first.
 *
 * The two answers are not the same question asked twice. Reading is what
 * historical playtests need to stay legible after a game is archived; writing
 * is what recording new evidence needs. An archived game grants the first and
 * refuses the second, which is exactly the behaviour playtest records require.
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
     * Determine whether the holder may see playtests of this game.
     */
    public function allowsReading(): bool
    {
        return $this->canRead;
    }

    /**
     * Determine whether the holder may record playtests against this game.
     */
    public function allowsWriting(): bool
    {
        return $this->canRead && $this->canWrite;
    }
}
