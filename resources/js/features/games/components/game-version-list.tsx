import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import versions from '@/routes/games/versions';
import type { GameVersion } from '../types/game';

type GameVersionListProps = {
    versions: GameVersion[];
    workspace: string;
    game: string;
};

/**
 * A game's iterations, newest first.
 *
 * The first entry is marked current because the server orders by version
 * number, which is the ordering the domain guarantees is unique and total —
 * so "the top one" and "the latest one" cannot disagree.
 *
 * There is no diff between versions. A version records that an iteration
 * existed and what changed in prose; comparing them needs design documents,
 * which do not exist yet.
 */
export default function GameVersionList({
    versions: list,
    workspace,
    game,
}: GameVersionListProps) {
    if (list.length === 0) {
        return (
            <div className="rounded-lg border border-dashed px-6 py-16 text-center">
                <p className="font-medium">No versions yet</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Cut one when a build is worth remembering — before a
                    playtest, say, or after a change you might want to undo.
                </p>
            </div>
        );
    }

    return (
        <ol className="divide-y rounded-lg border">
            {list.map((version, index) => (
                <li key={version.id} className="relative p-4">
                    <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <Link
                            href={versions.show({
                                workspace,
                                game,
                                version: version.version_number,
                            })}
                            className="font-medium after:absolute after:inset-0"
                        >
                            {version.label}
                        </Link>

                        {version.name && (
                            <span className="text-sm text-muted-foreground">
                                {version.name}
                            </span>
                        )}

                        {index === 0 && (
                            <Badge variant="secondary" className="ml-auto">
                                Current
                            </Badge>
                        )}
                    </div>

                    {version.description && (
                        <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
                            {version.description}
                        </p>
                    )}

                    <p className="mt-2 text-xs text-muted-foreground">
                        {version.creator?.name ?? 'Someone'}
                        {version.created_at &&
                            ` · ${new Date(version.created_at).toLocaleDateString()}`}
                    </p>
                </li>
            ))}
        </ol>
    );
}
