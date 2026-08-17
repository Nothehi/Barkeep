<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when something tries to build a version number that cannot exist.
 *
 * Never reachable from a request: version numbers are allocated by
 * `CreateFrameworkVersion` and never supplied by a caller. This guards the
 * domain against a bad value arriving from a migration, a seeder or a future
 * import.
 */
final class InvalidFrameworkVersionNumber extends FrameworkRuleViolation
{
    public static function forValue(int $value): self
    {
        return new self(__('Framework versions are numbered from one; :value is not a version.', ['value' => $value]));
    }
}
