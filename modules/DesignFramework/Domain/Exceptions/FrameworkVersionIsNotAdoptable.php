<?php

namespace Modules\DesignFramework\Domain\Exceptions;

use Modules\DesignFramework\Domain\Enums\FrameworkStatus;

/**
 * Raised when a game is pointed at a framework version it may not follow.
 *
 * Only published versions may be adopted. A draft is still being written, so a
 * game following it would find its questions changing between visits — which is
 * exactly the failure versioning exists to prevent, arrived at from the other
 * direction. An archived version is retired, and starting a new project on a
 * retired methodology is a mistake worth refusing rather than allowing quietly.
 *
 * Reported against `framework_version_id` so the framework picker shows it in
 * place: a designer choosing from a list wants to be told about the item they
 * chose.
 */
final class FrameworkVersionIsNotAdoptable extends FrameworkRuleViolation
{
    private function __construct(public readonly FrameworkStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(FrameworkStatus $status): self
    {
        return new self($status, match ($status) {
            FrameworkStatus::Draft => __('This framework version is still a draft and cannot be adopted yet.'),
            FrameworkStatus::Archived => __('This framework version has been archived and cannot be adopted.'),
            FrameworkStatus::Published => __('This framework version cannot be adopted.'),
        });
    }

    /**
     * Raised when the version is published but its framework has been retired.
     */
    public static function becauseFrameworkIsArchived(): self
    {
        return new self(
            FrameworkStatus::Archived,
            __('This framework has been archived and cannot be adopted.'),
        );
    }

    public function field(): string
    {
        return 'framework_version_id';
    }
}
