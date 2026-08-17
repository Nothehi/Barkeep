<?php

namespace Modules\DesignFramework\Domain\Events;

use DateTimeImmutable;

/**
 * Dispatched when a designer answers one of the framework's questions.
 *
 * Deliberately carries no response text. The answers to prompts like "what is
 * the most interesting decision in your game?" are a studio's design thinking,
 * and putting them into an event would push them into every log, queue payload
 * and consumer that ever subscribes. A consumer that genuinely needs the answer
 * can ask for it; nothing so far does.
 *
 * `wasRevised` distinguishes a first answer from a rewrite, which is the one
 * thing a consumer can usefully act on.
 */
final readonly class PromptAnswered
{
    public function __construct(
        public string $responseId,
        public string $gameFrameworkId,
        public string $gameId,
        public string $promptId,
        public bool $wasRevised,
        public string $answeredBy,
        public DateTimeImmutable $answeredAt,
    ) {}
}
