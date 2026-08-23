<?php

namespace Modules\GameRules\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleSet as RuleSetModel;
use Modules\Identity\Domain\Models\User;

/**
 * The rule set policy's answers, flattened into something the client can render.
 *
 * The client needs to know whether to draw an "Add rule" button, and the only
 * correct source for that is the policy itself. Computing it by re-reading
 * statuses and memberships in TypeScript is what makes an interface's idea of the
 * rules drift from the server's as the rules change.
 *
 * `canEdit` is the one nearly every control on the rules screens is gated on.
 * "May the rule system inside this set be changed?" is a single question with a
 * single answer, and giving the interface one boolean rather than sixteen — one
 * per kind of record — is what stops the two from drifting apart control by
 * control.
 *
 * `canClone` is the one that matters on an *active* set, where every other write
 * is refused. An interface that only knew `canEdit` would show a designer a
 * read-only screen and no way forward; showing "Clone to a new draft" is the
 * whole affordance section 55 of the brief depends on.
 *
 * These are hints for the interface, not grants. Every one is checked again on
 * the request that performs the action.
 */
final class RuleSetPermissions
{
    /**
     * The abilities the client is told about for a single rule set.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canRename' => 'rename',
        'canEdit' => 'edit',
        'canActivate' => 'activate',
        'canArchive' => 'archive',
        'canClone' => 'clone',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given rule set.
     *
     * @return array<string, bool>
     */
    public function for(User $user, RuleSet $ruleSet): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $ruleSet),
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
     * Whether the account may start a rule system for the given design state.
     *
     * Separate from the map above because it is a question about the version
     * rather than about any one set, and the rules screen needs the answer before
     * there is a set to ask about.
     */
    public function canCreateFor(?User $user, GameVersion $version): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', [RuleSetModel::class, $version]);
    }
}
