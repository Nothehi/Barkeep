<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * What the game a balance profile belongs to permits, said in this module's own
 * words.
 *
 * GameEconomy sits on top of GameDesign exactly as Playtesting and
 * PrototypeIteration do, and takes the same approach: everything it needs to
 * know about the project is reduced to two booleans and a reason, once, in the
 * adapter that reads GameDesign's policy.
 *
 * Workspace roles are conspicuously absent, and so are game statuses. Whether
 * somebody may read a game already accounts for their membership, the
 * workspace's own state and the game's status — GameDesign works all of that out,
 * and asking the same questions again here would be a second implementation of
 * the tenancy rules that could disagree with the first.
 *
 * The split between reading and writing is what makes historical balance
 * durable. An archived game still grants the first, so the numbers a convention
 * playtest ran against stay legible for as long as anybody wants to look at
 * them; it refuses the second, so nothing new is tuned into work that has been
 * put away.
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
     * Determine whether the holder may see this game's balance configuration.
     */
    public function allowsReading(): bool
    {
        return $this->canRead;
    }

    /**
     * Determine whether the holder may tune this game's economy.
     */
    public function allowsWriting(): bool
    {
        return $this->canRead && $this->canWrite;
    }
}
