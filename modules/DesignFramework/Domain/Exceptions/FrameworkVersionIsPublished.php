<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when something tries to change a published framework version.
 *
 * The module's central invariant, and the reason it is an exception rather than
 * only a policy check. The policy stops the request; this stops the
 * *operation*, including any caller that reaches a command without going
 * through HTTP — a seeder, a console command, a later module.
 *
 * What is frozen is not just the version row. Its phases, principles, criteria,
 * practices, prompts, checklists and checklist items are all frozen with it,
 * because games are already following it and their evaluations, completions and
 * responses point at those rows. Editing a published criterion would rewrite a
 * question after somebody had answered it.
 *
 * The way to change a published methodology is to create the next version.
 */
final class FrameworkVersionIsPublished extends FrameworkRuleViolation
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * Raised when the version itself, or anything inside it, was edited.
     */
    public static function andReadOnly(): self
    {
        return new self(__('This framework version has been published and is read-only. Create a new version to make changes.'));
    }

    /**
     * Raised when the version is fine but the framework around it is archived.
     */
    public static function becauseFrameworkIsArchived(): self
    {
        return new self(__('This framework has been archived and is read-only.'));
    }

    public function status(): int
    {
        return 409;
    }
}
