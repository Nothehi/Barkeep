<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The validated input for answering one of the framework's questions.
 *
 * One field, required. A prompt exists to be thought about, and an empty answer is
 * indistinguishable from not having answered — so blanking a response deletes it
 * rather than storing nothing, and that decision is the command's.
 */
final readonly class PromptResponseData
{
    public function __construct(public string $response) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(response: FrameworkInput::requiredText($input, 'response'));
    }
}
