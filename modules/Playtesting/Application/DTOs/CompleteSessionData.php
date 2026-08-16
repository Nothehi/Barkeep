<?php

namespace Modules\Playtesting\Application\DTOs;

/**
 * What a designer writes down as a session ends.
 *
 * Both optional. Ending a session has to be one press, because it happens
 * while people are standing up and putting the box away — a dialog that
 * demands a write-up first is a dialog that gets dismissed, and the session
 * never gets ended at all.
 *
 * The outcome is what this sitting settled; the notes are what happened in it.
 * A designer who has already been typing notes during the session leaves that
 * field alone, and what they wrote is kept.
 */
final readonly class CompleteSessionData
{
    public function __construct(
        public ?string $outcome = null,
        public ?string $notes = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            outcome: PlaytestInput::text($input, 'outcome'),
            notes: PlaytestInput::text($input, 'notes'),
        );
    }
}
