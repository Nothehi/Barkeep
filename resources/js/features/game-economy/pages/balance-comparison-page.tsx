import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import balance from '@/routes/balance';
import BalanceComparisonView from '../components/balance-comparison';
import { useProfileScope } from '../hooks/use-balance-scope';
import type {
    BalanceComparison,
    BalanceProfile,
    BalanceSnapshot,
} from '../types/game-economy';

type BalanceComparisonPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    profile: { data: BalanceProfile };
    snapshots: { data: BalanceSnapshot[] };
    comparison: { data: BalanceComparison };
};

/**
 * What changed between two frozen configurations.
 *
 * Its own page rather than a panel on the dashboard, because a comparison is something a studio shares —
 * "here is everything that moved between the convention build and the one we shipped" — and a URL carrying
 * both snapshot ids is what makes that possible.
 *
 * The direction is stated in the heading rather than left to be inferred. Every "10 → 12" below reads left
 * to right in the order shown at the top, which is the only way a diff can be trusted at a glance.
 */
export default function BalanceComparisonPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    profile: { data: profile },
    comparison: { data: comparison },
}: BalanceComparisonPageProps) {
    const { t } = useTranslation();
    const scope = useProfileScope(workspace, game, version, profile);

    return (
        <>
            <Head title={t('Compare snapshots · :game', { game: game.name })} />

            <div className="space-y-6 px-4 py-6">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={balance.show.url(scope)}>
                        <ArrowLeft className="size-4 rtl:rotate-180" />
                        {profile.name}
                    </Link>
                </Button>

                <Heading
                    variant="small"
                    title={t('Snapshot comparison')}
                    description={t(
                        'Everything that moved between two frozen states of this economy',
                    )}
                />

                <div className="flex flex-wrap items-center gap-3 rounded-md border p-3">
                    <span className="font-medium" dir="auto">
                        {comparison.from.name}
                    </span>

                    <ArrowRight className="size-4 text-muted-foreground rtl:rotate-180" />

                    <span className="font-medium" dir="auto">
                        {comparison.to.name}
                    </span>

                    <span className="ms-auto text-sm text-muted-foreground">
                        {t(':count changes', {
                            count: String(comparison.count),
                        })}
                    </span>
                </div>

                <BalanceComparisonView comparison={comparison} />
            </div>
        </>
    );
}
