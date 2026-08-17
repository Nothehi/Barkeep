<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;

/**
 * The validated input for writing or editing one piece of framework content.
 *
 * One DTO for all five types, mirroring the one Eloquent base they share. They
 * differ only in which body field they use — a description, a set of instructions,
 * a question — and each command reads the fields its own type has. A caller that
 * sends `instructions` to a principle has it validated away by the form request
 * before it reaches here.
 *
 * Two things are deliberately absent:
 *
 * - **position.** Allocated by `ContentSequencer` and changed only through an
 *   explicit reorder, so a caller cannot jump the queue by including one in an
 *   edit.
 * - **address.** Derived from the title once, on creation, and left alone
 *   afterwards so that it stays a stable handle.
 *
 * The phase is here and is nullable, and the null is meaningful: content filed
 * under no phase applies across the whole methodology. `sent('phase_id')`
 * distinguishes "leave it where it is" from "move it to the top level", which are
 * the same value and different intentions.
 */
final readonly class ContentData
{
    /**
     * @param  list<string>  $provided  the input keys the caller actually sent
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $instructions = null,
        public ?string $prompt = null,
        public ?string $phaseId = null,
        public ?FrameworkContentStatus $status = null,
        public array $provided = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $status = FrameworkInput::identifier($input, 'status');

        return new self(
            title: array_key_exists('title', $input) ? FrameworkInput::requiredText($input, 'title') : null,
            description: FrameworkInput::text($input, 'description'),
            instructions: FrameworkInput::text($input, 'instructions'),
            prompt: array_key_exists('prompt', $input) ? FrameworkInput::requiredText($input, 'prompt') : null,
            phaseId: FrameworkInput::identifier($input, 'phase_id'),
            status: $status === null ? null : FrameworkContentStatus::tryFrom($status),
            provided: array_keys($input),
        );
    }

    /**
     * Determine whether the caller sent the given field at all.
     */
    public function sent(string $field): bool
    {
        return in_array($field, $this->provided, strict: true);
    }

    /**
     * Determine whether this request asks to file the content under a phase, or
     * under none.
     */
    public function movesPhase(): bool
    {
        return $this->sent('phase_id');
    }
}
