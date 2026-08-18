import { Link } from '@inertiajs/react';
import {
    Compass,
    FlaskConical,
    GitBranch,
    LayoutDashboard,
    Settings,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import games from '@/routes/games';
import framework from '@/routes/games/framework';
import versions from '@/routes/games/versions';
import playtests from '@/routes/playtests';
import { useGamePermissions } from '../hooks/use-game-permissions';
import type { Game } from '../types/game';
import ChangeStatusDialog from './change-status-dialog';
import DesignPhaseBadge from './design-phase-badge';
import GameStatusBadge from './game-status-badge';

type GameHeaderProps = {
    game: Game;
    workspace: string;
};

/**
 * The heading and navigation shared by every game screen.
 *
 * The tabs are links rather than a tab component because each one is a real
 * page with its own URL — refreshing on the versions tab should land back on
 * the versions tab. Each one is added when the context behind it exists and
 * not before; playtests appeared with the Playtesting module.
 *
 * What the header offers is driven by the server's permission map. Hiding the
 * settings link from somebody who cannot use it is a courtesy, not a control:
 * the settings screen authorizes the request on its own.
 */
export default function GameHeader({ game, workspace }: GameHeaderProps) {
    const permissions = useGamePermissions(game);
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useTranslation();

    const overviewUrl = games.show({ workspace, game: game.slug });
    const versionsUrl = versions.index({ workspace, game: game.slug });
    const playtestsUrl = playtests.index({ workspace, game: game.slug });
    const frameworkUrl = framework.show({ workspace, game: game.slug });
    const settingsUrl = games.settings.edit({ workspace, game: game.slug });

    const tabs = [
        {
            url: overviewUrl,
            label: t('Overview'),
            icon: LayoutDashboard,
            shown: true,
        },
        {
            url: versionsUrl,
            label: t('Versions'),
            icon: GitBranch,
            shown: true,
        },
        {
            url: playtestsUrl,
            label: t('Playtests'),
            icon: FlaskConical,
            shown: true,
        },
        {
            url: frameworkUrl,
            label: t('Framework'),
            icon: Compass,
            shown: true,
        },
        {
            url: settingsUrl,
            label: t('Settings'),
            icon: Settings,
            shown: permissions.canUpdate,
        },
    ];

    return (
        <header className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-2">
                    <h1
                        className="truncate text-xl font-semibold tracking-tight"
                        dir="auto"
                    >
                        {game.name}
                    </h1>

                    <div className="flex flex-wrap items-center gap-2">
                        <GameStatusBadge
                            status={game.status}
                            label={game.status_label}
                        />
                        <DesignPhaseBadge
                            phase={game.design_phase}
                            label={game.design_phase_label}
                        />
                    </div>
                </div>

                <ChangeStatusDialog game={game} workspace={workspace} />
            </div>

            <nav
                className="flex flex-wrap items-center gap-2 border-b pb-2"
                aria-label={t('Game')}
            >
                {tabs
                    .filter((tab) => tab.shown)
                    .map((tab) => (
                        <Button
                            key={tab.label}
                            size="sm"
                            variant="ghost"
                            asChild
                            className={cn({
                                'bg-muted': isCurrentUrl(tab.url),
                            })}
                        >
                            <Link href={tab.url}>
                                <tab.icon className="size-4" />
                                {tab.label}
                            </Link>
                        </Button>
                    ))}
            </nav>
        </header>
    );
}
