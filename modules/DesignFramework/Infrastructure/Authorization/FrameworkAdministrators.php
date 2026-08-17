<?php

namespace Modules\DesignFramework\Infrastructure\Authorization;

use Illuminate\Contracts\Config\Repository;
use Modules\Identity\Domain\Models\User;

/**
 * The one place "may this account administer frameworks?" is answered.
 *
 * Frameworks are platform-wide. A methodology is adopted by studios across every
 * workspace, so editing one is not a workspace permission and no workspace role
 * should imply it — a studio owner administers their own studio, not Barkeep's
 * design methodology. Reusing `WorkspaceRole::Owner` here would be the easy thing
 * and would quietly hand every signed-up user the ability to rewrite the platform's
 * framework content.
 *
 * The context that will own this properly is Administration, and it does not
 * exist yet. So this is the temporary mechanism, made as small and obvious as
 * possible: a list of addresses in configuration, read here and nowhere else.
 * `FrameworkPolicy` is the only caller. When Administration arrives, this class
 * changes and nothing else does — which is the whole reason it is a class rather
 * than a `config()` call inside the policy.
 *
 * An empty list means nobody may write frameworks. That is the intended default:
 * reading published frameworks is open to every signed in account, and writing
 * them should require somebody having deliberately said who may.
 */
final class FrameworkAdministrators
{
    public function __construct(private readonly Repository $config) {}

    /**
     * Determine whether the given account may administer frameworks.
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
     * Determine whether anybody at all may administer frameworks.
     *
     * Used by the framework screens to explain themselves. Being told "no
     * framework administrators are configured" is far more useful than a bare
     * 403 to whoever is setting the platform up.
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
        $configured = $this->config->get('design-framework.administrators', []);

        if (! is_array($configured)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $value): string => is_string($value) ? trim($value) : '', $configured),
            fn (string $value): bool => $value !== '',
        ));
    }
}
