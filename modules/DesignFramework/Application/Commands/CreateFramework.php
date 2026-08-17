<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\DesignFramework\Application\DTOs\CreateFrameworkData;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Events\FrameworkCreated;
use Modules\DesignFramework\Domain\Exceptions\FrameworkSlugIsTaken;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;
use RuntimeException;

/**
 * Start writing a new methodology.
 *
 * A framework begins as a draft with no versions. That is deliberate rather than
 * incidental: the first thing an author does after creating one is open v1, and
 * creating both here would mean guessing that they want a version at all — an
 * imported or a placeholder framework may not.
 *
 * The status is not an input. Every framework starts as a draft, and anything sent
 * would be ignored, so it is not accepted.
 *
 * ## Addresses
 *
 * Framework addresses are globally unique, which makes allocation different from a
 * game's: there is no workspace to fall back on. Two paths, with different
 * behaviour on purpose:
 *
 * - an address the author typed is taken at their word. If it is in use, they are
 *   told, because silently making it `board-game-design-2` would leave them
 *   wondering why the URL they meant to publish is not the one they got.
 * - an address derived from the name is a suggestion, so a collision is resolved
 *   with a suffix rather than reported.
 *
 * The unique index is the real guard in both cases, and the retry below is what
 * turns losing a race into a round trip rather than an error page.
 */
final class CreateFramework
{
    /**
     * How many times to re-derive and try again after losing a race.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly FrameworkRepository $frameworks) {}

    public function handle(User $creator, CreateFrameworkData $data): Framework
    {
        $framework = $this->write($creator, $data);

        $framework->setRelation('creator', $creator);

        event(new FrameworkCreated(
            frameworkId: $framework->id,
            slug: $framework->slug,
            name: $framework->name,
            createdBy: $creator->id,
        ));

        return $framework;
    }

    /**
     * Allocate a free address and write the framework under it.
     */
    private function write(User $creator, CreateFrameworkData $data): Framework
    {
        $requested = $data->slug !== null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $slug = $this->allocate($data, $attempt);

            try {
                $framework = new Framework;

                $framework->fill([
                    'name' => $data->name,
                    'description' => $data->description,
                ]);

                $framework->slug = $slug->value;
                $framework->status = FrameworkStatus::default();
                $framework->created_by = $creator->id;

                $framework->save();

                return $framework;
            } catch (UniqueConstraintViolationException $collision) {
                /*
                 * Somebody took this address in the window between the check and the
                 * insert. An author who asked for a specific one is told; an author
                 * who let us derive one gets the next candidate.
                 */
                if ($requested) {
                    throw FrameworkSlugIsTaken::forSlug($slug->value);
                }

                continue;
            }
        }

        throw new RuntimeException(
            "Could not allocate a framework address for [{$data->name}] after ".self::MAX_ATTEMPTS.' attempts.',
        );
    }

    /**
     * Work out which address to try on this attempt.
     *
     * The first attempt uses what the author asked for, or the name-derived base. Each
     * subsequent attempt suffixes the base — and only ever when the address was
     * derived, because a requested one has already been refused by then.
     */
    private function allocate(CreateFrameworkData $data, int $attempt): FrameworkSlug
    {
        if ($data->slug !== null) {
            $slug = FrameworkSlug::fromString($data->slug);

            if ($this->frameworks->slugExists($slug)) {
                throw FrameworkSlugIsTaken::forSlug($slug->value);
            }

            return $slug;
        }

        $base = FrameworkSlug::fromName($data->name);

        if ($attempt === 1 && ! $this->frameworks->slugExists($base)) {
            return $base;
        }

        for ($suffix = max(2, $attempt); $suffix < max(2, $attempt) + 100; $suffix++) {
            $candidate = $base->withSuffix($suffix);

            if (! $this->frameworks->slugExists($candidate)) {
                return $candidate;
            }
        }

        return $base->withSuffix(bin2hex(random_bytes(4)));
    }
}
