import { Link } from '@inertiajs/react';
import { FileStack, Lock, Plus, Repeat } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import prototypes from '@/routes/prototypes';
import { createPrototypeVersion } from '../api';
import { emptyCreatePrototypeVersionInput } from '../schemas/prototype-iteration';
import type { PrototypeVersion } from '../types/prototype-iteration';

type PrototypeVersionListProps = {
    versions: PrototypeVersion[];
    workspace: string;
    game: string;
    prototype: string;
    canCreateVersion: boolean;
};

/**
 * Every recorded state of a prototype, newest first.
 *
 * Newest first because a prototype's versions are a stack rather than a sequence: what somebody reaching for
 * the list wants is the current build, and v1 is history.
 *
 * A version that has been iterated on is marked as part of the design record. That is the immutability rule
 * appearing in the interface rather than only in an error message — and the "cut next version" button beside
 * it is the reason the rule is reasonable: the way forward is always one click away, and it asks for nothing.
 */
export default function PrototypeVersionList({
    versions,
    workspace,
    game,
    prototype,
    canCreateVersion,
}: PrototypeVersionListProps) {
    const { t, choice } = useTranslation();
    const { formatDate } = useFormatters();

    return (
        <Card data-test="prototype-versions">
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="text-base">{t('Versions')}</CardTitle>

                    {canCreateVersion && (
                        <Button
                            size="sm"
                            onClick={() =>
                                createPrototypeVersion(
                                    { workspace, game },
                                    prototype,
                                    emptyCreatePrototypeVersionInput,
                                    { preserveScroll: false },
                                )
                            }
                            data-test="create-version-button"
                        >
                            <Plus className="size-4" />
                            {t('Cut next version')}
                        </Button>
                    )}
                </div>
            </CardHeader>

            <CardContent>
                {versions.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed py-8 text-center text-sm text-muted-foreground"
                        data-test="versions-empty"
                    >
                        {t(
                            'No versions yet. Cut one when you have something to put on the table — everything you test is recorded against a version.',
                        )}
                    </p>
                ) : (
                    <ul className="space-y-2" data-test="version-list">
                        {versions.map((version) => (
                            <li
                                key={version.id}
                                className="flex flex-wrap items-start justify-between gap-2 rounded-md border p-3"
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Link
                                            href={prototypes.versions.show.url({
                                                workspace,
                                                game,
                                                prototype,
                                                prototypeVersion:
                                                    version.version_number,
                                            })}
                                            className="text-sm font-medium hover:underline"
                                            data-test={`version-link-${version.version_number}`}
                                        >
                                            {version.label}
                                        </Link>

                                        {version.name && (
                                            <span
                                                className="text-sm text-muted-foreground"
                                                dir="auto"
                                            >
                                                {version.name}
                                            </span>
                                        )}

                                        {(version.iterations_count ?? 0) >
                                            0 && (
                                            <Badge
                                                variant="secondary"
                                                data-test={`version-frozen-${version.version_number}`}
                                            >
                                                <Lock className="size-3" />
                                                {t('Part of the record')}
                                            </Badge>
                                        )}
                                    </div>

                                    <p className="flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground">
                                        <span className="inline-flex items-center gap-1">
                                            <FileStack className="size-3" />
                                            {choice(
                                                ':count file|:count files',
                                                version.artifacts_count ?? 0,
                                            )}
                                        </span>

                                        <span className="inline-flex items-center gap-1">
                                            <Repeat className="size-3" />
                                            {choice(
                                                ':count iteration|:count iterations',
                                                version.iterations_count ?? 0,
                                            )}
                                        </span>

                                        {version.created_at && (
                                            <span>
                                                {formatDate(version.created_at)}
                                            </span>
                                        )}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
