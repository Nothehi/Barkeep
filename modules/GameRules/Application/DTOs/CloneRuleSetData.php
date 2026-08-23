<?php

namespace Modules\GameRules\Application\DTOs;

/**
 * The validated input required to copy a rule set into a fresh draft.
 *
 * Both fields are optional, and that is the point. Cloning is the module's answer
 * to "I want to change the rules that are in play", so it has to succeed with one
 * press — a form that demanded a name first would be a small tax on the operation
 * section 55 of the brief wants people to reach for. When no name is given the
 * repository picks one the version does not already use.
 */
final readonly class CloneRuleSetData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: RuleInput::text($input, 'name'),
            description: RuleInput::text($input, 'description'),
        );
    }
}
