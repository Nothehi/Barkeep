<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when an iteration names a prototype version from another game.
 *
 * The other half of the central invariant, and the more dangerous half. A game
 * version from elsewhere is caught by GameDesign's own scoping the moment
 * anybody looks; a *prototype* version is this module's own record, so nothing
 * outside the module would notice a mismatch — the iteration would read
 * perfectly and describe a cycle nobody ran, against a build from a different
 * project.
 *
 * There is a test that attempts exactly that forgery: game A's iteration
 * pointing at game B's prototype version. It has to fail, and this is what it
 * fails with.
 *
 * The failure is structural rather than a comparison. The version is only ever
 * looked up *through* the game's own prototypes, so a version belonging to
 * somebody else's game is not found and rejected — it never resolves. This is
 * what that non-resolution is reported as.
 */
final class PrototypeVersionDoesNotBelongToGame extends IterationRuleViolation
{
    private function __construct(
        public readonly string $gameId,
        public readonly string $prototypeVersionId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $gameId, string $prototypeVersionId): self
    {
        /*
         * Worded as "not a prototype of this game" rather than "not found",
         * because both possible causes — a version that does not exist and one
         * belonging to another studio's game — must read the same to the
         * caller. Distinguishing them would confirm the existence of records
         * outside their workspace.
         */
        return new self($gameId, $prototypeVersionId, __('That is not a prototype version of this game.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'prototype_version_id';
    }
}
