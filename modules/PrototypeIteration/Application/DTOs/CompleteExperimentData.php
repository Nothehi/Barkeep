<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * The half of an experiment written down after it runs.
 *
 * Two fields, and keeping them separate is the point. "Players explored more
 * strategies but sessions ran twenty minutes longer" is a result — something
 * somebody watched happen. "Unlimited actions improve strategy but harm pacing"
 * is a conclusion — an argument drawn from it. Only the second is a judgement,
 * and a reader deserves to see which is which rather than being handed one field
 * containing both.
 *
 * The result is required and the conclusion is not, which follows from the same
 * distinction. What happened is a fact the person who ran the session already
 * has; what it means often arrives days later, and demanding it at the same
 * moment would produce conclusions written to fill a field.
 */
final readonly class CompleteExperimentData
{
    public function __construct(
        public string $actualResult,
        public ?string $conclusion = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            actualResult: IterationInput::requiredText($input, 'actual_result'),
            conclusion: IterationInput::text($input, 'conclusion'),
        );
    }
}
