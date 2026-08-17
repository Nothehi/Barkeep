<?php

namespace Modules\GameDesign\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;

/**
 * The mechanic policy's answers, flattened into something the client can render.
 *
 * The vocabulary screen needs to know whether to draw an "Add mechanic" button,
 * and the only correct source for that is the policy. Working it out in
 * TypeScript by comparing an email against a configured list is what makes an
 * interface's idea of the rules drift from the server's.
 *
 * These are hints for the interface, not grants. Every ability is checked again
 * on the request that performs the action.
 */
final class MechanicPermissions
{
    /**
     * The abilities the client is told about for one term.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canArchive' => 'archive',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given mechanic.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Mechanic $mechanic): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $mechanic),
            self::ABILITIES,
        );
    }

    /**
     * Determine whether the given account may add to the vocabulary.
     *
     * Asked without a mechanic, because there is nothing to ask about yet.
     */
    public function canCreate(?User $user): bool
    {
        return $user instanceof User
            && $this->gate->forUser($user)->allows('create', Mechanic::class);
    }

    /**
     * The answer for a caller with no session.
     *
     * @return array<string, bool>
     */
    public static function none(): array
    {
        return array_map(fn (string $ability): bool => false, self::ABILITIES);
    }
}
