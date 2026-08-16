<?php

namespace Modules\GameDesign\Domain\ValueObjects;

/**
 * What the workspace around a game permits, said in GameDesign's own words.
 *
 * GameDesign builds on Workspace but must not reimplement it. This is the
 * whole of what it needs to know: can this account see inside the boundary at
 * all, may things inside it still change, and does the account administer it.
 *
 * Roles are deliberately absent. Translating a workspace role into these
 * three answers happens once, in the adapter that reads Workspace — which is
 * what stops "admin means X here" from being decided a second time inside
 * this module and drifting from the first.
 */
final readonly class WorkspaceGrant
{
    public function __construct(
        public bool $isMember,
        public bool $isReadable,
        public bool $allowsChanges,
        public bool $canAdminister,
        public ?string $deniedReason = null,
    ) {}

    /**
     * The grant of somebody who does not belong to the workspace at all.
     */
    public static function none(): self
    {
        return new self(
            isMember: false,
            isReadable: false,
            allowsChanges: false,
            canAdminister: false,
        );
    }

    /**
     * Determine whether the holder may read what is inside the workspace.
     */
    public function allowsReading(): bool
    {
        return $this->isMember && $this->isReadable;
    }

    /**
     * Determine whether the holder may change what is inside the workspace.
     */
    public function allowsWriting(): bool
    {
        return $this->allowsReading() && $this->allowsChanges;
    }
}
