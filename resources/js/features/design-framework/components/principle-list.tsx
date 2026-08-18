import { Lightbulb } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import type { DesignPrinciple } from '../types/framework';

type PrincipleListProps = {
    principles: DesignPrinciple[];
};

/**
 * The design rules this phase asks a designer to hold in mind.
 *
 * Nothing to tick, and nothing that counts. There is nothing to *do* with a
 * principle, and a checkbox that advanced a bar when somebody confirmed they
 * had read one would be measuring reading.
 *
 * Drawn first on the phase page for the same reason: it is what to keep in
 * view while working through everything below it.
 */
export default function PrincipleList({ principles }: PrincipleListProps) {
    const { t } = useTranslation();

    if (principles.length === 0) {
        return null;
    }

    return (
        <section className="space-y-3" data-test="principle-list">
            <h2 className="text-sm font-medium">{t('Principles')}</h2>

            <ul className="grid gap-3">
                {principles.map((principle) => (
                    <li
                        key={principle.id}
                        className="flex gap-3 rounded-lg border bg-muted/30 px-4 py-3"
                        data-test={`principle-${principle.id}`}
                    >
                        <Lightbulb className="mt-0.5 size-4 shrink-0 text-muted-foreground" />

                        <div className="min-w-0 space-y-1">
                            <p className="text-sm font-medium" dir="auto">
                                {principle.title}
                            </p>

                            {principle.description && (
                                <p
                                    className="text-sm text-muted-foreground"
                                    dir="auto"
                                >
                                    {principle.description}
                                </p>
                            )}
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}
