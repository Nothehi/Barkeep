<?php

namespace Modules\PrototypeIteration\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * The iteration policy's answers, flattened into something the client can render.
 *
 * The iteration screen is the busiest in the module — it offers changes,
 * experiments, decisions, playtest attachments and three lifecycle moves — so it
 * asks the most permission questions, and every one of them is answered here by
 * the policy rather than inferred client side from a status.
 *
 * `canRecordWork` is the one worth pointing at. Almost every control on the
 * screen is gated on it, because "may design work be added to this cycle?" is a
 * single question with a single answer, and giving the interface one boolean
 * rather than eight is what stops the two from drifting apart control by
 * control.
 *
 * This is a hint for the interface, not a grant. Every one of these abilities is
 * checked again on the request that performs the action.
 */
final class IterationPermissions
{
    /**
     * The abilities the client is told about for a single iteration.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canStart' => 'start',
        'canComplete' => 'complete',
        'canCancel' => 'cancel',
        'canRecordWork' => 'recordWork',
        'canAttachPlaytest' => 'attachPlaytest',
        'canCreateGameVersion' => 'createGameVersion',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given iteration.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Iteration $iteration): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $iteration),
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
     * Whether the account may plan an iteration against the given game.
     *
     * Separate from the map above because it is a question about the game rather
     * than about any one iteration, and the iterations screen needs the answer
     * before there is an iteration to ask about.
     */
    public function canCreateFor(?User $user, Game $game): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', [Iteration::class, $game]);
    }
}
