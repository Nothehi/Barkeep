<?php

namespace Modules\GameDesign\Domain\Events;

/**
 * Dispatched when something is decided about a game's design.
 *
 * The one event in this module that other contexts have a real reason to care
 * about: a methodology's factual criteria are answered from this record, so
 * "the player count was decided" is the moment a framework's progress can move
 * without anybody having ticked anything.
 *
 * `changed` names the fields rather than carrying their values. A consumer that
 * needs to know what the design now says should read the record — which is one
 * query and always current — rather than trust a payload that was true when the
 * event was queued. Carrying the values would also push a studio's design
 * thinking into every log and queue that ever subscribes.
 *
 * `mechanics` is listed in `changed` like any other field when the set a game
 * claims has been altered.
 */
final readonly class DesignRecordUpdated
{
    /**
     * @param  list<string>  $changed  The fields that actually changed.
     */
    public function __construct(
        public string $designRecordId,
        public string $gameId,
        public string $workspaceId,
        public string $updatedBy,
        public array $changed,
    ) {}
}
