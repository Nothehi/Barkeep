<?php

namespace Modules\GameDesign\Infrastructure\Authorization;

use Illuminate\Contracts\Config\Repository;
use Modules\Identity\Domain\Models\User;

/**
 * The one place "may this account curate the mechanics vocabulary?" is answered.
 *
 * The vocabulary is platform-wide: every studio picks from one list, and that
 * is the only reason the list is worth having. So editing it is not a workspace
 * permission and no workspace role should imply it — a studio owner curates
 * their own games, not the words other people's games are described with.
 * Reusing `WorkspaceRole::Owner` here would be the easy thing and would let any
 * signed-up account rename a term that appears on somebody else's design.
 *
 * The context that will own this properly is Administration, and it does not
 * exist yet. So this is the temporary mechanism, made as small and obvious as
 * possible: a list of addresses in configuration, read here and nowhere else,
 * with `MechanicPolicy` as the only caller. When Administration arrives, this
 * class changes and nothing else does.
 *
 * Deliberately a separate list from the framework administrators, even though
 * the shape is identical. Writing a methodology and maintaining a taxonomy are
 * different jobs, and folding them into one setting would make that a decision
 * nobody gets to revisit.
 *
 * An empty list means nobody may write mechanics. That is the intended default:
 * reading the vocabulary and tagging your own game with it is open to every
 * signed in account, and changing what the words mean should require somebody
 * having deliberately said who may.
 */
final class MechanicCurators
{
    public function __construct(private readonly Repository $config) {}

    /**
     * Determine whether the given account may curate the vocabulary.
     *
     * Compared case-insensitively, because an email address is not case
     * sensitive in the part that matters and a configuration list is typed by
     * hand.
     */
    public function includes(User $user): bool
    {
        $email = mb_strtolower(trim($user->email));

        if ($email === '') {
            return false;
        }

        foreach ($this->addresses() as $address) {
            if (mb_strtolower($address) === $email) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether anybody at all may curate the vocabulary.
     *
     * Used by the mechanics screen to explain itself. Being told "no curators
     * are configured" is far more useful than a missing button to whoever is
     * setting the platform up.
     */
    public function anyConfigured(): bool
    {
        return $this->addresses() !== [];
    }

    /**
     * The configured addresses.
     *
     * @return list<string>
     */
    private function addresses(): array
    {
        $configured = $this->config->get('game-design.curators', []);

        if (! is_array($configured)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $value): string => is_string($value) ? trim($value) : '', $configured),
            fn (string $value): bool => $value !== '',
        ));
    }
}
