<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a designer declares something that moves the economy.
 *
 * Fires for the action alone, before it costs or pays anything. That is what an
 * action is when it is created — "we need a Build action" comes before anybody
 * has decided what it takes — so a consumer must not read this as a complete
 * description of what the action does.
 */
final readonly class EconomyActionCreated
{
    public function __construct(
        public string $actionId,
        public string $profileId,
        public string $slug,
    ) {}
}
