<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;

/**
 * The validated input for writing or editing one stage of a methodology.
 *
 * A phase has a name rather than a title, which is the one place the module's
 * vocabulary differs between phases and the content filed under them — "Core loop"
 * is a name, "Does the core loop work?" is a title.
 *
 * There is no position and no address. The position is allocated by
 * `ContentSequencer` and changed only by an explicit reorder, so a caller cannot
 * jump the queue by including one in an edit; the address is derived from the name
 * once, on creation, and then left alone so that a bookmarked phase URL survives a
 * rename.
 */
final readonly class PhaseData
{
    /**
     * @param  list<string>  $provided  the input keys the caller actually sent
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
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
            name: array_key_exists('name', $input) ? FrameworkInput::requiredText($input, 'name') : null,
            description: FrameworkInput::text($input, 'description'),
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
}
