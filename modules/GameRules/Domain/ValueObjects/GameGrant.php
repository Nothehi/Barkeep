<?php

namespace Modules\GameRules\Domain\ValueObjects;

/**
 * What a game permits this account, reduced to the two questions this module
 * asks.
 *
 * GameDesign's policy already accounts for workspace membership, the workspace's
 * status and the game's own. This is that answer, flattened: may the caller read
 * the rules, and may they change them. Everything in the module's policy is
 * written against these two booleans rather than against roles, which is what
 * keeps the tenancy rules to a single implementation.
 *
 * The denial message travels with the grant so a refusal can carry GameDesign's
 * own wording — somebody is told the game is archived rather than just "no".
 */
final readonly class GameGrant
{
    public function __construct(
        public bool $canRead,
        public bool $canWrite,
        public ?string $deniedReason = null,
    ) {}

    /**
     * The grant given to somebody who cannot see the game at all.
     *
     * Turned into a 404 by the policy rather than a 403: a rule set must not
     * confirm that a game exists to somebody who was not allowed to know.
     */
    public static function none(): self
    {
        return new self(canRead: false, canWrite: false);
    }

    public function allowsReading(): bool
    {
        return $this->canRead;
    }

    public function allowsWriting(): bool
    {
        return $this->canRead && $this->canWrite;
    }
}
