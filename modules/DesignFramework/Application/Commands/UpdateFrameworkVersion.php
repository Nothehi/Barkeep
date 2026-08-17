<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\FrameworkVersionData;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Change a draft edition's name or description.
 *
 * Refused once the version is published, by the same guard that refuses every edit to
 * its content. The version's own record is frozen with the phases inside it because
 * a game citing "v1: First public edition" should keep citing the same thing.
 *
 * The number is not editable and is not an input. It is the identifier games adopt.
 */
final class UpdateFrameworkVersion
{
    public function __construct(private readonly FrameworkModificationGuard $guard) {}

    public function handle(User $actor, FrameworkVersion $version, FrameworkVersionData $data): FrameworkVersion
    {
        $this->guard->ensureVersionIsModifiable($version);

        if ($data->sent('name')) {
            $version->name = $data->name;
        }

        if ($data->sent('description')) {
            $version->description = $data->description;
        }

        $version->save();

        return $version;
    }
}
