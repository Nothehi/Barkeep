import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Lock } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Game } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import prototypes from '@/routes/prototypes';
import ArtifactList from '../components/artifact-list';
import { usePrototypePermissions } from '../hooks/use-permissions';
import type {
    Prototype,
    PrototypeArtifact,
    PrototypeVersion,
} from '../types/prototype-iteration';

type PrototypeVersionPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    prototype: { data: Prototype };
    version: { data: PrototypeVersion };
    artifacts: { data: PrototypeArtifact[] };
};

/**
 * One state of a prototype and the files that make it buildable again.
 *
 * There is no edit form on this page, and the notice explaining why is the point rather than an apology. A
 * version anything has been iterated on is the answer to "what was actually on the table", and editing it
 * afterwards would rewrite what every record pointing at it says happened. The way forward is v-next, which
 * the prototype screen offers in one click.
 *
 * Files stay uploadable on a frozen version, because a print sheet filed later documents what the version was
 * rather than changing it.
 */
export default function PrototypeVersionPage({
    workspace: { data: workspace },
    game: { data: game },
    prototype: { data: prototype },
    version: { data: version },
    artifacts: { data: artifacts },
}: PrototypeVersionPageProps) {
    const { t, choice } = useTranslation();
    const permissions = usePrototypePermissions(prototype);
    const isFrozen = (version.iterations_count ?? 0) > 0;

    return (
        <>
            <Head
                title={t(':version of :prototype', {
                    version: version.label,
                    prototype: prototype.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <Button variant="ghost" size="sm" asChild className="-ms-2">
                    <Link
                        href={prototypes.show.url({
                            workspace: workspace.slug,
                            game: game.slug,
                            prototype: prototype.id,
                        })}
                    >
                        <ChevronLeft className="size-4 rtl:rotate-180" />
                        <span dir="auto">{prototype.name}</span>
                    </Link>
                </Button>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={
                            version.name
                                ? `${version.label} · ${version.name}`
                                : version.label
                        }
                        description={
                            version.description ??
                            t('One recorded state of this prototype.')
                        }
                    />

                    {isFrozen && (
                        <Badge variant="secondary" data-test="version-frozen">
                            <Lock className="size-3" />
                            {choice(
                                'Used by :count iteration|Used by :count iterations',
                                version.iterations_count ?? 0,
                            )}
                        </Badge>
                    )}
                </div>

                {isFrozen && (
                    <p
                        className="rounded-md border border-dashed p-3 text-sm text-muted-foreground"
                        data-test="version-frozen-notice"
                    >
                        {t(
                            'This version is part of the design record, so it cannot be changed. Cut the next version instead — everything already recorded against this one stays true.',
                        )}
                    </p>
                )}

                <ArtifactList
                    artifacts={artifacts}
                    workspace={workspace.slug}
                    game={game.slug}
                    prototype={prototype.id}
                    prototypeVersion={version.version_number}
                    canManage={permissions.canUpdate}
                />
            </div>
        </>
    );
}
