import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { useFormatters } from '@/lib/i18n';

type StatTileProps = {
    label: string;
    value: number;
    icon: LucideIcon;

    /**
     * The second line: what the headline number does not say on its own.
     * Absent when there is nothing worth adding, rather than being padded with
     * a restatement of the label.
     */
    hint?: string;

    /**
     * Where the number is explained in full, when there is such a place.
     * Tiles without one are not links: a figure the interface cannot take you
     * to should not pretend to be clickable.
     */
    href?: string;
};

/**
 * One figure on the home screen.
 *
 * The number is formatted through the locale rather than interpolated, so a
 * workspace read in Persian shows Persian digits — the rest of the page has
 * just promised that.
 */
export default function StatTile({
    label,
    value,
    icon: Icon,
    hint,
    href,
}: StatTileProps) {
    const { formatNumber } = useFormatters();

    return (
        <Card
            className={
                href
                    ? 'relative transition-colors hover:border-ring'
                    : undefined
            }
        >
            <CardContent className="flex items-start justify-between gap-3">
                <div className="min-w-0 space-y-1">
                    <p className="truncate text-sm text-muted-foreground">
                        {href ? (
                            <Link
                                href={href}
                                className="after:absolute after:inset-0"
                            >
                                {label}
                            </Link>
                        ) : (
                            label
                        )}
                    </p>

                    <p className="text-2xl font-semibold tracking-tight">
                        {formatNumber(value)}
                    </p>

                    {hint && (
                        <p className="truncate text-xs text-muted-foreground">
                            {hint}
                        </p>
                    )}
                </div>

                <Icon className="size-5 shrink-0 text-muted-foreground" />
            </CardContent>
        </Card>
    );
}
