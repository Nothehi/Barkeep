<?php

namespace Modules\GameDesign\Application\Commands;

use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\Exceptions\MechanicIsNotModifiable;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;

/**
 * Retire a term from the vocabulary.
 *
 * The only way a mechanic leaves. It stops being offered to games that have not
 * claimed it, and the games that already did keep saying what they said —
 * deleting the row would reach into other people's design records and quietly
 * remove a word they used to describe themselves, which is not a curator's to
 * do and is why `MechanicPolicy::delete()` refuses everybody.
 *
 * Terminal, for now. Un-retiring a term is a reasonable thing to want and is
 * not implemented, because "bring it back" and "it was never gone" are
 * different claims and only one of them is true — a curator who retired
 * something by mistake can add it again, which produces an honest new row
 * rather than a history that appears not to have happened.
 */
final class ArchiveMechanic
{
    /**
     * @throws MechanicIsNotModifiable when the term is already retired
     */
    public function handle(User $curator, Mechanic $mechanic): Mechanic
    {
        if ($mechanic->isArchived()) {
            throw MechanicIsNotModifiable::forStatus($mechanic->status);
        }

        $mechanic->status = MechanicStatus::Archived;
        $mechanic->save();

        return $mechanic;
    }
}
