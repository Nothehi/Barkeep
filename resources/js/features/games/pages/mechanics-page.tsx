import { Head } from '@inertiajs/react';
import { Info } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useTranslation } from '@/lib/i18n';
import CreateMechanicDialog from '../components/create-mechanic-dialog';
import MechanicList from '../components/mechanic-list';
import type { Mechanic, MechanicOptions } from '../types/mechanic';

type MechanicsPageProps = {
    mechanics: { data: Mechanic[] };
    options: MechanicOptions;
    can: { create: boolean };
    curation_configured: boolean;
};

/**
 * The platform's design vocabulary.
 *
 * Lives outside every workspace, which is the interface telling the truth about
 * the domain: these words are not a studio's. Two games that both use worker
 * placement have to say so with the same word, or nothing about them can ever
 * be compared — and that only works if there is one list.
 *
 * Everybody reads it; a curator writes it. Retired terms are shown to curators
 * and hidden from everybody else, so a designer is never offered a word the
 * platform has withdrawn while the person who withdrew it can still see that
 * they did.
 */
export default function MechanicsPage({
    mechanics: { data },
    options,
    can,
    curation_configured: curationConfigured,
}: MechanicsPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Design vocabulary')} />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={t('Design vocabulary')}
                        description={t(
                            'The mechanics a game can describe itself with',
                        )}
                    />

                    {can.create && <CreateMechanicDialog options={options} />}
                </div>

                {/*
                 * An installation with nobody configured to curate shows a
                 * read-only vocabulary and says why. That is far more useful to
                 * whoever is setting Barkeep up than a missing button they
                 * cannot account for.
                 */}
                {!curationConfigured && (
                    <Alert data-test="curation-not-configured">
                        <Info className="size-4" />
                        <AlertTitle>
                            {t('The vocabulary is read-only here')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'No accounts are configured to curate design mechanics, so nobody can add or edit one. Set',
                            )}
                            <code
                                className="mx-1 rounded bg-muted px-1 py-0.5 text-xs"
                                dir="ltr"
                            >
                                game-design.curators
                            </code>
                            {t('to change that.')}
                        </AlertDescription>
                    </Alert>
                )}

                <MechanicList mechanics={data} options={options} />
            </div>
        </>
    );
}
