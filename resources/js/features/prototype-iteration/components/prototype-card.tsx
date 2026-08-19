import { Link } from '@inertiajs/react';
import { Boxes, GitBranch, Layers } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import prototypes from '@/routes/prototypes';
import type { PrototypeCard as PrototypeCardData } from '../types/prototype-iteration';
import { PrototypeStatusBadge } from './status-badges';

type PrototypeCardProps = {
    prototype: PrototypeCardData;
    workspace: string;
    game: string;
};

/**
 * One prototype in a list.
 *
 * The version count is the figure that earns its place. A prototype on v1 and a prototype on v9 represent
 * completely different amounts of work, and it is the fastest way to see which approach a studio actually
 * pursued — so it sits beside the kind rather than being buried on the detail screen.
 */
export default function PrototypeCard({
    prototype,
    workspace,
    game,
}: PrototypeCardProps) {
    const { t, choice } = useTranslation();

    return (
        <Card className="transition-colors hover:border-primary/40">
            <CardHeader className="gap-2">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Link
                        href={prototypes.show.url({
                            workspace,
                            game,
                            prototype: prototype.id,
                        })}
                        className="min-w-0 font-medium hover:underline"
                        data-test={`prototype-link-${prototype.id}`}
                        dir="auto"
                    >
                        {prototype.name}
                    </Link>

                    <PrototypeStatusBadge
                        status={prototype.status}
                        label={prototype.status_label}
                    />
                </div>
            </CardHeader>

            <CardContent className="space-y-3">
                {prototype.description && (
                    <p
                        className="line-clamp-2 text-sm text-muted-foreground"
                        dir="auto"
                    >
                        {prototype.description}
                    </p>
                )}

                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <Badge variant="outline">
                        <Boxes className="size-3" />
                        {prototype.type_label}
                    </Badge>

                    <span className="inline-flex items-center gap-1">
                        <Layers className="size-3" />
                        {choice(
                            ':count version|:count versions',
                            prototype.versions_count ?? 0,
                        )}
                    </span>

                    {prototype.version_label && (
                        <span className="inline-flex items-center gap-1">
                            <GitBranch className="size-3" />
                            {t('Built from :version', {
                                version: prototype.version_label,
                            })}
                        </span>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
