import { cn } from '@/lib/utils';

type SectionHeadingProps = {
    /**
     * The two or three words above the title. It names the section rather
     * than describing it, and is what the header's anchors are promising.
     */
    eyebrow: string;

    title: string;
    description?: string;
    className?: string;
};

/**
 * The heading every section on the landing page opens with.
 *
 * Centred, and deliberately narrow: a marketing paragraph that runs the full
 * width of a six-column container is one nobody finishes reading. The three
 * pieces are passed already translated, so this stays a layout component and
 * can be read in one glance.
 */
export default function SectionHeading({
    eyebrow,
    title,
    description,
    className,
}: SectionHeadingProps) {
    return (
        <div className={cn('mx-auto max-w-2xl text-center', className)}>
            <p className="text-sm font-medium text-amber-600 dark:text-amber-400">
                {eyebrow}
            </p>

            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                {title}
            </h2>

            {description && (
                <p className="mt-4 text-base leading-relaxed text-pretty text-muted-foreground">
                    {description}
                </p>
            )}
        </div>
    );
}
