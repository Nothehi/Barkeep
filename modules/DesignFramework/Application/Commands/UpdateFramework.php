<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\UpdateFrameworkData;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Exceptions\FrameworkSlugIsTaken;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Change a framework's name, address or description.
 *
 * Only while it is a draft. Once a framework is published, those three fields are
 * what games and prose cite, and renaming the methodology under everybody following
 * it would make every reference to it wrong. Its *versions* are a different matter —
 * a published framework happily gains new drafts.
 *
 * Only the fields the caller actually sent are written, which is what lets a form
 * save a name without blanking the description beside it.
 *
 * An address change is refused rather than suffixed when it collides. This is
 * somebody typing a specific address; quietly giving them a different one would
 * leave them publishing a URL they did not choose.
 */
final class UpdateFramework
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
    ) {}

    public function handle(User $actor, Framework $framework, UpdateFrameworkData $data): Framework
    {
        $this->guard->ensureFrameworkIsModifiable($framework);

        if ($data->sent('name') && $data->name !== null) {
            $framework->name = $data->name;
        }

        if ($data->sent('description')) {
            $framework->description = $data->description;
        }

        if ($data->sent('slug') && $data->slug !== null) {
            $framework->slug = $this->rename($framework, $data->slug)->value;
        }

        $framework->save();

        return $framework;
    }

    /**
     * Validate a new address and prove it is free.
     *
     * The framework is excluded from the collision check so that re-submitting a form
     * without changing the address is not an error.
     *
     * @throws FrameworkSlugIsTaken
     */
    private function rename(Framework $framework, string $requested): FrameworkSlug
    {
        $slug = FrameworkSlug::fromString($requested);

        if ($this->frameworks->slugExists($slug, exceptId: (string) $framework->getKey())) {
            throw FrameworkSlugIsTaken::forSlug($slug->value);
        }

        return $slug;
    }
}
