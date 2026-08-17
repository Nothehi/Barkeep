<?php

namespace Modules\DesignFramework\Application\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\DesignFramework\Domain\Exceptions\InvalidContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use RuntimeException;

/**
 * The one place framework content gets an address.
 *
 * Content addresses are derived from titles and never typed. A framework author
 * writing "Does the game provide meaningful decisions?" is writing prose, and
 * asking them to also invent `meaningful-decisions` would be asking them to think
 * about URLs while thinking about design. The one address that is actually visible
 * — a phase's, in `/versions/1/phases/core-loop` — reads perfectly when derived
 * from the phase name.
 *
 * Two properties matter more than the derivation itself:
 *
 * - **addresses are stable.** Renaming content does not re-derive its address.
 *   The slug is the handle a seeder, an import or a future export addresses
 *   content by, so rebuilding v1 twice has to produce the same identifiers — and a
 *   phase URL somebody bookmarked should not break because the title gained a
 *   comma. Commands therefore allocate an address on creation and leave it alone
 *   afterwards.
 * - **collisions are resolved, not reported.** Two criteria in one version can
 *   legitimately be titled similarly, and refusing the second because its derived
 *   address was taken would be a rule about slugs imposed on somebody writing
 *   sentences. The second gets `-2`.
 *
 * The uniqueness scope arrives as a query rather than being inferred, because it
 * differs by type: content is unique within its framework version, and a checklist
 * item within its checklist.
 */
final class ContentSlugAllocator
{
    /**
     * How many suffixed candidates to try before giving up.
     *
     * A hundred pieces of content in one version with the same derived address is
     * not a collision, it is a bug or an attack, and looping forever would turn
     * either into a hung request.
     */
    private const MAX_ATTEMPTS = 100;

    /**
     * Derive a free address from a title.
     *
     * @param  Builder<covariant Model>  $peers  the set the address must be unique within
     * @param  string|null  $exceptKey  the row being renamed, so it does not collide with itself
     *
     * @throws InvalidContentSlug when the title contains nothing sluggable
     */
    public function derive(Builder $peers, string $title, ?string $exceptKey = null): ContentSlug
    {
        $base = ContentSlug::fromTitle($title);

        if (! $this->taken($peers, $base, $exceptKey)) {
            return $base;
        }

        for ($suffix = 2; $suffix <= self::MAX_ATTEMPTS; $suffix++) {
            $candidate = $base->withSuffix($suffix);

            if (! $this->taken($peers, $candidate, $exceptKey)) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            "Could not allocate a free address derived from [{$title}] after ".self::MAX_ATTEMPTS.' attempts.',
        );
    }

    /**
     * Determine whether an address is already in use within the given set.
     *
     * @param  Builder<covariant Model>  $peers
     */
    private function taken(Builder $peers, ContentSlug $slug, ?string $exceptKey): bool
    {
        $query = (clone $peers)->where('slug', $slug->value);

        if ($exceptKey !== null) {
            $query->whereKeyNot($exceptKey);
        }

        return $query->exists();
    }
}
