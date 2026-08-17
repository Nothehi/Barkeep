<?php

namespace Modules\DesignFramework\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * The framework policy's answers, flattened into something the client can render.
 *
 * The builder needs to know whether to draw an "Add phase" button, and the only correct
 * source for that is the policy itself. Working it out in TypeScript by comparing a version
 * status against a configured administrator list is what makes an interface's idea of the
 * rules drift from the server's as the rules change — and here the rules include the one
 * invariant the module cannot afford to get wrong.
 *
 * `canUpdateVersion` in particular is doing double duty: it is the answer to "may this
 * account edit?" *and* to "is this version still a draft?". The screens need both and the
 * policy already folds them together, which is why the read-only published builder is not a
 * separate flag the client could forget to check.
 *
 * These are hints for the interface, not grants. Every one of these abilities is checked
 * again on the request that actually performs the action.
 */
final class FrameworkPermissions
{
    /**
     * The abilities the client is told about for a framework.
     *
     * @var array<string, string>
     */
    private const FRAMEWORK_ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canPublish' => 'publish',
        'canArchive' => 'archive',
        'canCreateVersion' => 'createVersion',
    ];

    /**
     * The abilities the client is told about for one edition.
     *
     * @var array<string, string>
     */
    private const VERSION_ABILITIES = [
        'canView' => 'viewVersion',
        'canUpdate' => 'updateVersion',
        'canPublish' => 'publishVersion',
        'canArchive' => 'archiveVersion',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given framework.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Framework $framework): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $framework),
            self::FRAMEWORK_ABILITIES,
        );
    }

    /**
     * The all-denied map, for callers with no account.
     *
     * @return array<string, bool>
     */
    public static function none(): array
    {
        return array_fill_keys(array_keys(self::FRAMEWORK_ABILITIES), false);
    }

    /**
     * Resolve what the given account may do with the given edition.
     *
     * @return array<string, bool>
     */
    public function forVersion(User $user, FrameworkVersion $version): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $version),
            self::VERSION_ABILITIES,
        );
    }

    /**
     * The all-denied map for an edition.
     *
     * @return array<string, bool>
     */
    public static function noneForVersion(): array
    {
        return array_fill_keys(array_keys(self::VERSION_ABILITIES), false);
    }

    /**
     * Whether the account may start a new methodology.
     *
     * Separate from the maps above because it is a question about nothing in particular, and
     * the frameworks list needs the answer before there is a framework to ask about.
     */
    public function canCreate(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', Framework::class);
    }
}
