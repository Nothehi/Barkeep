<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\GameDesign\Application\DTOs\MechanicData;
use Modules\GameDesign\Application\Services\MechanicSlugAllocator;
use Modules\GameDesign\Domain\Exceptions\InvalidMechanicSlug;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;

/**
 * Change what a term is called or what it means.
 *
 * Worth stating plainly, because it is unusual for this module: this edits
 * something every workspace can see. A definition improving improves it on
 * every game that claimed the term, which is the intended behaviour of a shared
 * vocabulary and precisely why the ability is not handed to studio owners.
 *
 * The address follows the name. A term renamed from "Drafting" to "Card
 * drafting" moves to `card-drafting`, and the old address stops resolving —
 * mechanic addresses are conveniences rather than permanent identifiers, and
 * nothing in the platform stores one. Games point at the row by id, so a rename
 * is invisible to every design record that claimed it.
 *
 * A save that changes nothing is a no-op, so a curator opening a term and
 * pressing save does not touch its timestamp.
 */
final class UpdateMechanic
{
    public function __construct(private readonly MechanicSlugAllocator $slugs) {}

    /**
     * @throws InvalidMechanicSlug when the name contains nothing sluggable
     */
    public function handle(User $curator, Mechanic $mechanic, MechanicData $data): Mechanic
    {
        $mechanic->fill([
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $mechanic->category = $data->category;

        if ($mechanic->isDirty('name')) {
            $mechanic->slug = $this->slugs->allocate($data->name, (string) $mechanic->getKey())->value;
        }

        if ($mechanic->getDirty() === []) {
            return $mechanic;
        }

        try {
            $mechanic->save();
        } catch (UniqueConstraintViolationException) {
            throw InvalidMechanicSlug::taken($mechanic->slug);
        }

        return $mechanic;
    }
}
