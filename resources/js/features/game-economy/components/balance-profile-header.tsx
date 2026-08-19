import { Link } from '@inertiajs/react';
import { Archive, CheckCircle2, GitBranch } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import { activateBalanceProfile, archiveBalanceProfile } from '../api';
import type { ProfileScope } from '../hooks/use-balance-scope';
import { useBalancePermissions } from '../hooks/use-permissions';
import type { BalanceProfile } from '../types/game-economy';
import { BalanceProfileStatusBadge } from './status-badges';

type BalanceProfileHeaderProps = {
    profile: BalanceProfile;
    scope: ProfileScope;
    versionLabel: string;
    processing?: boolean;
    onLifecycle?: () => void;
};

/**
 * What this configuration is, and what can be done to it.
 *
 * The version label is the part that earns its place. A profile without it is a page of numbers with no
 * date on it — "the economy as of v4" is how a designer places what they are looking at, and it is the
 * reason this module hangs off a version at all.
 *
 * The lifecycle buttons are drawn from `available_transitions`, which the server derives from the matrix and
 * filters through the policy. So an archived profile offers nothing, a reader offers nothing, and neither
 * fact is decided here.
 */
export default function BalanceProfileHeader({
    profile,
    scope,
    versionLabel,
    processing = false,
    onLifecycle,
}: BalanceProfileHeaderProps) {
    const { t } = useTranslation();
    const permissions = useBalancePermissions(profile);

    const move = (status: string) => {
        const options = { onFinish: () => onLifecycle?.() };

        if (status === 'active') {
            activateBalanceProfile(scope, options);

            return;
        }

        archiveBalanceProfile(scope, options);
    };

    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="min-w-0 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h1 className="text-xl font-semibold" dir="auto">
                        {profile.name}
                    </h1>

                    <BalanceProfileStatusBadge
                        status={profile.status}
                        label={profile.status_label}
                    />

                    {profile.is_active && (
                        <span className="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                            <CheckCircle2 className="size-3" />
                            {t('In play')}
                        </span>
                    )}
                </div>

                <p className="inline-flex items-center gap-1 text-sm text-muted-foreground">
                    <GitBranch className="size-3" />
                    {t('The economy as of :version', { version: versionLabel })}
                </p>

                {profile.description && (
                    <p
                        className="max-w-2xl text-sm text-muted-foreground"
                        dir="auto"
                    >
                        {profile.description}
                    </p>
                )}
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" asChild>
                    <Link
                        href={balance.index.url({
                            workspace: scope.workspace,
                            game: scope.game,
                            version: scope.version,
                        })}
                    >
                        {t('All profiles')}
                    </Link>
                </Button>

                {profile.available_transitions.map((transition) => (
                    <Button
                        key={transition.status}
                        size="sm"
                        variant={
                            transition.status === 'archived'
                                ? 'outline'
                                : 'default'
                        }
                        disabled={processing}
                        onClick={() => move(transition.status)}
                        data-test={`profile-${transition.status}-button`}
                    >
                        {processing && <Spinner />}
                        {transition.status === 'archived' ? (
                            <Archive className="size-4" />
                        ) : (
                            <CheckCircle2 className="size-4" />
                        )}
                        {transition.label}
                    </Button>
                ))}

                {!permissions.canConfigure && !profile.is_active && (
                    <span className="text-xs text-muted-foreground">
                        {t('Read only')}
                    </span>
                )}
            </div>
        </div>
    );
}
