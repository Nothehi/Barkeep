<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when a game records work against content from a framework version it
 * is not following.
 *
 * The check this backs is what keeps a game's progress honest. A criterion id, a
 * practice id, a checklist item id and a prompt id all arrive in URLs, and none
 * of them says which version it came from. Resolving each one *through* the
 * version the game actually adopted means an id from v2 is not found for a game
 * on v1 — so there is no path on which a game answers a question it was never
 * asked.
 *
 * It is also the cross-workspace defence for these routes. A criterion belongs to
 * a globally published version, so a criterion id is not secret; what stops one
 * studio writing into another's progress is that the game and its adoption are
 * resolved from the URL and the content is resolved through them.
 */
final class ContentDoesNotBelongToFrameworkVersion extends FrameworkRuleViolation
{
    private function __construct(
        public readonly string $frameworkVersionId,
        public readonly string $contentId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $frameworkVersionId, string $contentId): self
    {
        return new self(
            $frameworkVersionId,
            $contentId,
            __('That does not belong to the framework version this game is following.'),
        );
    }
}
