<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * The half of an experiment written down before it runs.
 *
 * Question, hypothesis, method, expected result — in that order, because that is
 * the order they have to be decided in for the exercise to mean anything. This
 * DTO deliberately cannot carry a result: recording what happened is a separate
 * action with a separate shape, which is what stops a single request from
 * inventing a prediction and its confirmation in one go.
 *
 * Only the question is required. "Let us run it and watch" is real method, and
 * demanding a hypothesis for exploratory work would produce invented ones — which
 * is worse than none, because an invented prediction that happens to come true
 * looks like insight.
 */
final readonly class DesignExperimentData
{
    public function __construct(
        public string $title,
        public string $question,
        public ?string $hypothesis = null,
        public ?string $method = null,
        public ?string $expectedResult = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            title: IterationInput::requiredText($input, 'title'),
            question: IterationInput::requiredText($input, 'question'),
            hypothesis: IterationInput::text($input, 'hypothesis'),
            method: IterationInput::text($input, 'method'),
            expectedResult: IterationInput::text($input, 'expected_result'),
        );
    }
}
