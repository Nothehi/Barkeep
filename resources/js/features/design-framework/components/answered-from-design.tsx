import { Link } from '@inertiajs/react';
import { Check, PencilLine } from 'lucide-react';
import { cn } from '@/lib/utils';

type AnsweredFromDesignProps = {
    /** What the designer needs to have written down, worded by the server. */
    label: string;

    /** Whether they have. */
    recorded: boolean;

    /** Where to go and write it down. */
    settingsUrl: string;

    className?: string;
};

/**
 * Where a fact-backed question's answer comes from.
 *
 * Drawn in place of grade buttons or a checkbox, because there is nothing here to
 * assess. "Are the player count and playing time decided?" has one honest answer
 * and it is a lookup — offering four grades for it would be asking a designer to
 * have an opinion about whether they had typed something.
 *
 * The unanswered state is a link rather than a warning. The work is not "tick
 * this", it is "go and decide the player count", and the row should take you
 * there rather than tell you off.
 */
export default function AnsweredFromDesign({
    label,
    recorded,
    settingsUrl,
    className,
}: AnsweredFromDesignProps) {
    if (recorded) {
        return (
            <p
                className={cn(
                    'inline-flex items-center gap-1.5 text-sm text-muted-foreground',
                    className,
                )}
                data-test="answered-from-design"
            >
                <Check className="size-4 text-primary" />
                Answered by {label} in this game&apos;s design.
            </p>
        );
    }

    return (
        <Link
            href={settingsUrl}
            className={cn(
                'inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 hover:underline',
                className,
            )}
            data-test="unanswered-from-design"
        >
            <PencilLine className="size-4" />
            Record {label} to answer this.
        </Link>
    );
}
