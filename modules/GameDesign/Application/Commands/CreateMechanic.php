<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\GameDesign\Application\DTOs\MechanicData;
use Modules\GameDesign\Application\Services\MechanicSlugAllocator;
use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\Exceptions\InvalidMechanicSlug;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;

/**
 * Add a term to the platform's design vocabulary.
 *
 * A new mechanic is available immediately. There is no draft state to work up
 * from, because a mechanic is a word with a definition — there is nothing to
 * refine in private, and a half-written term that games could not yet claim
 * would be a row nobody could use and everybody could see.
 *
 * No event is emitted, and that is deliberate rather than an omission. Nothing
 * in the platform can act on a word having been coined; an event that existed
 * only for symmetry would be an event somebody subscribes to by mistake. When
 * something downstream genuinely needs to know — a search index, say — that is
 * the moment to add one and the moment its shape will be knowable.
 */
final class CreateMechanic
{
    public function __construct(private readonly MechanicSlugAllocator $slugs) {}

    /**
     * @throws InvalidMechanicSlug when the name contains nothing sluggable
     */
    public function handle(User $curator, MechanicData $data): Mechanic
    {
        $mechanic = new Mechanic;

        $mechanic->fill([
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $mechanic->slug = $this->slugs->allocate($data->name)->value;
        $mechanic->category = $data->category;
        $mechanic->status = MechanicStatus::default();

        try {
            $mechanic->save();
        } catch (UniqueConstraintViolationException) {
            /*
             * Two curators allocated the same address between the check and the
             * insert. The index is what actually holds the rule; this turns its
             * refusal into the module's own message rather than a 500.
             */
            throw InvalidMechanicSlug::taken($mechanic->slug);
        }

        return $mechanic;
    }
}
