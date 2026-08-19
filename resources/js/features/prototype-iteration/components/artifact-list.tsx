import { Download, FileStack, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { artifactDownloadUrl, createArtifact, deleteArtifact } from '../api';
import {
    ARTIFACT_MAX_KILOBYTES,
    isWithinArtifactSizeLimit,
} from '../schemas/prototype-iteration';
import type { PrototypeArtifact } from '../types/prototype-iteration';

type ArtifactListProps = {
    artifacts: PrototypeArtifact[];
    workspace: string;
    game: string;
    prototype: string;
    prototypeVersion: number;
    canManage: boolean;
};

/**
 * The files that make one state of a prototype buildable again.
 *
 * Upload, download, delete, and nothing else — no folders, no renaming, no revisions of a single file. A
 * second upload is a second artifact, and a corrected asset usually means the next prototype version anyway.
 *
 * Downloads are ordinary links to a route that authorizes and then streams. There is no public URL for an
 * artifact and no signed one either: a studio's unreleased card art must not be reachable by anybody holding
 * a link.
 *
 * The size is checked here before the upload starts, because the alternative is somebody watching a large
 * file finish uploading and then being told no.
 */
export default function ArtifactList({
    artifacts,
    workspace,
    game,
    prototype,
    prototypeVersion,
    canManage,
}: ArtifactListProps) {
    const { t } = useTranslation();
    const fileInput = useRef<HTMLInputElement>(null);
    const [error, setError] = useState<string | undefined>(undefined);
    const [uploading, setUploading] = useState(false);

    const upload = (file: File | null) => {
        if (!file) {
            return;
        }

        if (!isWithinArtifactSizeLimit(file)) {
            setError(
                t('That file is larger than the :size MB limit.', {
                    size: Math.round(ARTIFACT_MAX_KILOBYTES / 1024),
                }),
            );

            return;
        }

        setError(undefined);
        setUploading(true);

        createArtifact(
            { workspace, game },
            prototype,
            prototypeVersion,
            { file, name: '', type: '' },
            {
                onError: (errors) => setError(errors.file ?? errors.name),
                onFinish: () => {
                    setUploading(false);

                    if (fileInput.current) {
                        fileInput.current.value = '';
                    }
                },
            },
        );
    };

    return (
        <Card data-test="artifacts">
            <CardHeader>
                <CardTitle className="text-base">{t('Files')}</CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {artifacts.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed py-8 text-center text-sm text-muted-foreground"
                        data-test="artifacts-empty"
                    >
                        {t(
                            'No files yet. Attach the print sheets, card layouts or exported build that make this version buildable again.',
                        )}
                    </p>
                ) : (
                    <ul className="space-y-2" data-test="artifact-list">
                        {artifacts.map((artifact) => (
                            <li
                                key={artifact.id}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-3"
                                data-test={`artifact-${artifact.id}`}
                            >
                                <div className="flex min-w-0 items-center gap-2">
                                    <FileStack className="size-4 shrink-0 text-muted-foreground" />

                                    <span
                                        className="min-w-0 truncate text-sm"
                                        dir="auto"
                                    >
                                        {artifact.name}
                                    </span>

                                    <Badge variant="outline">
                                        {artifact.type_label}
                                    </Badge>

                                    {artifact.size_label && (
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {artifact.size_label}
                                        </span>
                                    )}
                                </div>

                                <div className="flex items-center gap-1">
                                    <Button variant="ghost" size="sm" asChild>
                                        <a
                                            href={artifactDownloadUrl(
                                                { workspace, game },
                                                prototype,
                                                prototypeVersion,
                                                artifact.id,
                                            )}
                                            aria-label={t('Download file')}
                                            data-test={`download-artifact-${artifact.id}`}
                                        >
                                            <Download className="size-3.5" />
                                        </a>
                                    </Button>

                                    {canManage && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                deleteArtifact(
                                                    { workspace, game },
                                                    prototype,
                                                    prototypeVersion,
                                                    artifact.id,
                                                )
                                            }
                                            aria-label={t('Remove file')}
                                            data-test={`delete-artifact-${artifact.id}`}
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                {canManage && (
                    <div className="grid gap-2">
                        <Label htmlFor="artifact-file" className="text-xs">
                            {t('Attach a file')}
                        </Label>

                        <div className="flex flex-wrap items-center gap-2">
                            <Input
                                id="artifact-file"
                                ref={fileInput}
                                type="file"
                                className="max-w-sm"
                                disabled={uploading}
                                onChange={(event) =>
                                    upload(event.target.files?.[0] ?? null)
                                }
                                data-test="artifact-file-input"
                            />

                            {uploading && (
                                <span className="inline-flex items-center gap-2 text-xs text-muted-foreground">
                                    <Spinner />
                                    {t('Uploading')}
                                </span>
                            )}

                            {!uploading && (
                                <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <Upload className="size-3" />
                                    {t('Up to :size MB', {
                                        size: Math.round(
                                            ARTIFACT_MAX_KILOBYTES / 1024,
                                        ),
                                    })}
                                </span>
                            )}
                        </div>

                        <InputError message={error} />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
