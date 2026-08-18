import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import frameworks from '@/routes/frameworks';
import { moveFramework } from '../api';
import CreateVersionDialog from '../components/create-version-dialog';
import FrameworkStatusBadge from '../components/framework-status-badge';
import TransitionButtons from '../components/transition-buttons';
import VersionList from '../components/version-list';
import type {
    Framework,
    FrameworkOptions,
    FrameworkStatus,
    FrameworkVersion,
} from '../types/framework';

type FrameworkPageProps = {
    framework: { data: Framework };
    versions: { data: FrameworkVersion[] };
    options: FrameworkOptions;
};

/**
 * One methodology: what it is, and which editions exist.
 *
 * The editions are the substance of this screen. A framework row is a name and
 * a description; the phases, criteria and practices all live inside an
 * edition, which is why the list below is the page rather than a tab on it.
 *
 * Publishing a framework and publishing an edition are different acts, and the
 * screen keeps them apart. A published framework with only draft editions is a
 * normal state — it means the methodology exists and there is nothing to adopt
 * yet.
 */
export default function FrameworkPage({
    framework: { data: framework },
    versions: { data: versions },
}: FrameworkPageProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={framework.name} />

            <div className="space-y-6 px-4 py-6">
                <Button size="sm" variant="ghost" asChild className="-ms-2">
                    <Link href={frameworks.index.url()}>
                        <ChevronLeft className="size-4 rtl:rotate-180" />
                        {t('All frameworks')}
                    </Link>
                </Button>

                <header className="space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0 space-y-2">
                            <h1
                                className="truncate text-xl font-semibold tracking-tight"
                                dir="auto"
                            >
                                {framework.name}
                            </h1>

                            <FrameworkStatusBadge
                                status={framework.status}
                                label={framework.status_label}
                            />
                        </div>

                        <TransitionButtons
                            transitions={framework.available_transitions}
                            testPrefix="framework-transition"
                            onMove={(status, done) =>
                                moveFramework(
                                    framework.slug,
                                    status as Exclude<FrameworkStatus, 'draft'>,
                                    { onFinish: done },
                                )
                            }
                        />
                    </div>

                    {framework.description && (
                        <p
                            className="max-w-3xl text-sm text-muted-foreground"
                            dir="auto"
                        >
                            {framework.description}
                        </p>
                    )}
                </header>

                <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <h2 className="text-sm font-medium">{t('Editions')}</h2>

                    <CreateVersionDialog
                        framework={framework}
                        versions={versions}
                    />
                </div>

                <VersionList framework={framework} versions={versions} />
            </div>
        </>
    );
}
