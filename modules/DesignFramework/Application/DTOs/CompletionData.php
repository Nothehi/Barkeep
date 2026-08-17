<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The validated input for ticking — or unticking — a practice or a checklist item.
 *
 * Both are binary and both are recorded by the existence of a row, so both take
 * the same two fields and share this DTO.
 *
 * `completed` defaults to true, which is what a plain "Mark complete" button sends.
 * Passing false is how the tick is taken back: the command deletes the completion
 * rather than storing a false flag, which is what keeps the state genuinely binary
 * and a completion record a record of a completion.
 */
final readonly class CompletionData
{
    public function __construct(
        public bool $completed = true,
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
            completed: FrameworkInput::flag($input, 'completed', default: true),
            notes: FrameworkInput::text($input, 'notes'),
        );
    }
}
