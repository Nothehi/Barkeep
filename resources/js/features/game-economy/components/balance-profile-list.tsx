import { Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import type { BalanceScope } from '../hooks/use-balance-scope';
import type { BalanceProfile } from '../types/game-economy';
import { BalanceProfileStatusBadge } from './status-badges';

type BalanceProfileListProps = {
    profiles: BalanceProfile[];
    scope: BalanceScope;
};

/**
 * The balance configurations of one design state.
 *
 * A version usually has one, sometimes three: the draft somebody is tuning, the one in play, and the
 * archived ones that came before. The status badge is what tells them apart, which is why it sits beside the
 * name rather than in a column further right.
 *
 * The counts are the second thing a reader wants — an empty profile and one with eight resources and
 * fourteen actions look identical without them.
 */
export default function BalanceProfileList({
    profiles,
    scope,
}: BalanceProfileListProps) {
    const { t, choice } = useTranslation();

    if (profiles.length === 0) {
        return (
            <p
                className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                data-test="profiles-empty"
            >
                {t(
                    'No balance profile for this version yet. A profile holds the numbers: what resources exist, what actions cost, and what you are tuning.',
                )}
            </p>
        );
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {profiles.map((profile) => (
                <Card key={profile.id} data-test={`profile-${profile.id}`}>
                    <CardHeader className="gap-2 pb-3">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <Link
                                href={balance.show.url({
                                    ...scope,
                                    profile: profile.id,
                                })}
                                className="min-w-0 font-medium hover:underline"
                                dir="auto"
                                data-test={`profile-link-${profile.id}`}
                            >
                                {profile.name}
                            </Link>

                            <BalanceProfileStatusBadge
                                status={profile.status}
                                label={profile.status_label}
                            />
                        </div>

                        {profile.is_active && (
                            <p className="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                <CheckCircle2 className="size-3" />
                                {t('In play')}
                            </p>
                        )}
                    </CardHeader>

                    <CardContent className="space-y-2">
                        {profile.description && (
                            <p
                                className="line-clamp-2 text-sm text-muted-foreground"
                                dir="auto"
                            >
                                {profile.description}
                            </p>
                        )}

                        <p className="flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                            <span>
                                {choice(
                                    ':count resource|:count resources',
                                    profile.resources_count ?? 0,
                                )}
                            </span>

                            <span>
                                {choice(
                                    ':count action|:count actions',
                                    profile.actions_count ?? 0,
                                )}
                            </span>

                            <span>
                                {choice(
                                    ':count variable|:count variables',
                                    profile.variables_count ?? 0,
                                )}
                            </span>
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
