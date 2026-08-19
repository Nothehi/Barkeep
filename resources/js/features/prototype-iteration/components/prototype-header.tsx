import { Boxes, GitBranch, Layers } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { archivePrototype } from '../api';
import { usePrototypePermissions } from '../hooks/use-permissions';
import type { Prototype } from '../types/prototype-iteration';
import { PrototypeStatusBadge } from './status-badges';

type PrototypeHeaderProps = {
    prototype: Prototype;
    workspace: string;
    game: string;
};

/**
 * The heading of a prototype: what it is, and what can still be done to it.
 *
 * Archival is the only action offered, and it is drawn from the server's transition list rather than from a
 * status check here. It is also confirmed before it fires, because it cannot be undone — a studio picking the
 * approach back up creates a new prototype rather than reopening this one.
 */
export default function PrototypeHeader({
    prototype,
    workspace,
    game,
}: PrototypeHeaderProps) {
    const { t, choice } = useTranslation();
    const permissions = usePrototypePermissions(prototype);

    const offersArchive = prototype.available_transitions.some(
        (move) => move.status === 'archived',
    );

    const archive = () => {
        const confirmed = window.confirm(
            t(
                'Archive this prototype? Its versions and iterations stay readable, but nothing new can be added and it cannot be reopened.',
            ),
        );

        if (confirmed) {
            archivePrototype({ workspace, game }, prototype.id);
        }
    };

    return (
        <header className="space-y-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-2">
                    <h1
                        className="text-xl font-semibold tracking-tight"
                        dir="auto"
                    >
                        {prototype.name}
                    </h1>

                    <div className="flex flex-wrap items-center gap-2">
                        <PrototypeStatusBadge
                            status={prototype.status}
                            label={prototype.status_label}
                        />

                        <Badge variant="outline">
                            <Boxes className="size-3" />
                            {prototype.type_label}
                        </Badge>
                    </div>
                </div>

                {offersArchive && permissions.canArchive && (
                    <Button
                        variant="outline"
                        onClick={archive}
                        data-test="archive-prototype-button"
                    >
                        {t('Archive prototype')}
                    </Button>
                )}
            </div>

            {prototype.description && (
                <p
                    className="max-w-3xl text-sm text-muted-foreground"
                    dir="auto"
                >
                    {prototype.description}
                </p>
            )}

            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                {prototype.version?.label && (
                    <span className="inline-flex items-center gap-1">
                        <GitBranch className="size-3" />
                        {t('Built from :version', {
                            version: prototype.version.label,
                        })}
                    </span>
                )}

                <span className="inline-flex items-center gap-1">
                    <Layers className="size-3" />
                    {choice(
                        ':count version|:count versions',
                        prototype.versions_count ?? 0,
                    )}
                </span>
            </div>
        </header>
    );
}
