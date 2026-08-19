<?php

namespace Modules\GameEconomy\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * The balance policy's answers, flattened into something the client can render.
 *
 * The client needs to know whether to draw an "Add resource" button, and the
 * only correct source for that is the policy itself. Computing it by re-reading
 * statuses and memberships in TypeScript is what makes an interface's idea of
 * the rules drift from the server's as the rules change.
 *
 * `canConfigure` is the one nearly every control on the balance screens is gated
 * on. "May the configuration inside this profile be changed?" is a single
 * question with a single answer, and giving the interface one boolean rather
 * than nine — one per kind of record — is what stops the two from drifting apart
 * control by control.
 *
 * These are hints for the interface, not grants. Every one is checked again on
 * the request that performs the action.
 */
final class BalanceProfilePermissions
{
    /**
     * The abilities the client is told about for a single profile.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canActivate' => 'activate',
        'canArchive' => 'archive',
        'canConfigure' => 'configure',
        'canCreateSnapshot' => 'createSnapshot',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given profile.
     *
     * @return array<string, bool>
     */
    public function for(User $user, BalanceProfile $profile): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $profile),
            self::ABILITIES,
        );
    }

    /**
     * The all-denied map, for callers with no account.
     *
     * @return array<string, bool>
     */
    public static function none(): array
    {
        return array_fill_keys(array_keys(self::ABILITIES), false);
    }

    /**
     * Whether the account may start a configuration for the given design state.
     *
     * Separate from the map above because it is a question about the version
     * rather than about any one profile, and the balance screen needs the answer
     * before there is a profile to ask about.
     */
    public function canCreateFor(?User $user, GameVersion $version): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', [BalanceProfile::class, $version]);
    }
}
