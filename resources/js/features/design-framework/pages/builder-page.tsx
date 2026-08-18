import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, Lock, Users } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useFormatters, useTranslation } from '@/lib/i18n';
import frameworks from '@/routes/frameworks';
import { moveVersion } from '../api';
import type { BuilderRow } from '../components/builder-section';
import BuilderSection from '../components/builder-section';
import FrameworkStatusBadge from '../components/framework-status-badge';
import TransitionButtons from '../components/transition-buttons';
import type {
    Checklist,
    DesignCriterion,
    DesignPhase,
    DesignPractice,
    DesignPrinciple,
    DesignPrompt,
    Framework,
    FrameworkStatus,
    FrameworkVersion,
} from '../types/framework';

type BuilderPageProps = {
    framework: { data: Framework };
    version: { data: FrameworkVersion };
    phases: { data: DesignPhase[] };
    principles: { data: DesignPrinciple[] };
    criteria: { data: DesignCriterion[] };
    practices: { data: DesignPractice[] };
    prompts: { data: DesignPrompt[] };
    checklists: { data: Checklist[] };
};

/**
 * Where one edition of a methodology is written.
 *
 * The whole screen is arranged around a single fact the server sends:
 * `is_editable`. A draft edition can be extended and reordered; a published
 * one is frozen, for everybody, including the administrator who wrote it. That
 * is not a permission being withheld — it is what makes a game on v1 able to
 * keep reading the same questions for as long as it exists.
 *
 * An author sees their own unfinished content here, because they are the
 * person writing it. The game-facing screens never do.
 */
export default function BuilderPage({
    framework: { data: framework },
    version: { data: version },
    phases: { data: phases },
    principles: { data: principles },
    criteria: { data: criteria },
    practices: { data: practices },
    prompts: { data: prompts },
    checklists: { data: checklists },
}: BuilderPageProps) {
    const { t, choice } = useTranslation();
    const { formatNumber } = useFormatters();
    const editable = version.is_editable && version.permissions.canUpdate;

    return (
        <>
            <Head
                title={t(':edition · :framework', {
                    edition: version.label,
                    framework: framework.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <Button size="sm" variant="ghost" asChild className="-ms-2">
                    <Link
                        href={frameworks.show.url({
                            framework: framework.slug,
                        })}
                    >
                        <ChevronLeft className="size-4 rtl:rotate-180" />
                        <span dir="auto">{framework.name}</span>
                    </Link>
                </Button>

                <header className="space-y-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0 space-y-2">
                            <h1 className="truncate text-xl font-semibold tracking-tight">
                                {version.label}
                                {version.name && (
                                    <span
                                        className="ms-2 font-normal text-muted-foreground"
                                        dir="auto"
                                    >
                                        {version.name}
                                    </span>
                                )}
                            </h1>

                            <div className="flex flex-wrap items-center gap-2">
                                <FrameworkStatusBadge
                                    status={version.status}
                                    label={version.status_label}
                                />

                                {version.adoptions_count !== undefined &&
                                    version.adoptions_count > 0 && (
                                        <span className="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                            <Users className="size-3.5" />
                                            {choice(
                                                ':count game following|:count games following',
                                                version.adoptions_count,
                                            )}
                                        </span>
                                    )}
                            </div>
                        </div>

                        <TransitionButtons
                            transitions={version.available_transitions}
                            testPrefix="version-transition"
                            onMove={(status, done) =>
                                moveVersion(
                                    framework.slug,
                                    version.version_number,
                                    status as Exclude<FrameworkStatus, 'draft'>,
                                    { onFinish: done },
                                )
                            }
                        />
                    </div>

                    {version.description && (
                        <p
                            className="max-w-3xl text-sm text-muted-foreground"
                            dir="auto"
                        >
                            {version.description}
                        </p>
                    )}
                </header>

                {!version.is_editable && (
                    <Alert data-test="version-frozen">
                        <Lock className="size-4" />
                        <AlertTitle>{t('This edition is frozen')}</AlertTitle>
                        <AlertDescription>
                            {t(
                                'Publishing an edition fixes its content, so the games following it keep answering the questions they were actually asked. Cut a new edition to change anything.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                <BuilderSection
                    framework={framework.slug}
                    version={version.version_number}
                    type="phases"
                    heading={t('Phases')}
                    description={t(
                        'The arc of the methodology, in the order it is worked through.',
                    )}
                    rows={phases.map(toPhaseRow)}
                    phases={phases}
                    editable={editable}
                    fields={{
                        primary: 'name',
                        secondary: 'description',
                    }}
                    filed={false}
                />

                <BuilderSection
                    framework={framework.slug}
                    version={version.version_number}
                    type="principles"
                    heading={t('Principles')}
                    description={t(
                        'Design rules to hold in mind. Nothing to tick, and nothing that counts towards progress.',
                    )}
                    rows={principles.map((row) => toRow(row, row.description))}
                    phases={phases}
                    editable={editable}
                    fields={{ primary: 'title', secondary: 'description' }}
                />

                <BuilderSection
                    framework={framework.slug}
                    version={version.version_number}
                    type="criteria"
                    heading={t('Criteria')}
                    description={t(
                        'The questions the methodology asks of a design. A game grades itself against each one.',
                    )}
                    rows={criteria.map((row) => toRow(row, row.description))}
                    phases={phases}
                    editable={editable}
                    fields={{ primary: 'title', secondary: 'description' }}
                />

                <BuilderSection
                    framework={framework.slug}
                    version={version.version_number}
                    type="practices"
                    heading={t('Practices')}
                    description={t(
                        'Activities a studio carries out and ticks off.',
                    )}
                    rows={practices.map((row) =>
                        toRow(row, row.instructions ?? row.description),
                    )}
                    phases={phases}
                    editable={editable}
                    fields={{
                        primary: 'title',
                        secondary: 'instructions',
                        secondaryLabel: t('Instructions'),
                    }}
                />

                <BuilderSection
                    framework={framework.slug}
                    version={version.version_number}
                    type="prompts"
                    heading={t('Questions to answer')}
                    description={t(
                        'Open questions a studio answers in prose. Reported beside progress, never counted into it.',
                    )}
                    rows={prompts.map((row) => toRow(row, row.prompt))}
                    phases={phases}
                    editable={editable}
                    fields={{
                        primary: 'title',
                        secondary: 'prompt',
                        secondaryLabel: t('The question'),
                    }}
                />

                <BuilderSection
                    framework={framework.slug}
                    version={version.version_number}
                    type="checklists"
                    heading={t('Checklists')}
                    description={t(
                        'Lists of requirements. Required items count towards progress; optional ones do not.',
                    )}
                    rows={checklists.map((row) =>
                        toRow(
                            row,
                            row.items_count === undefined
                                ? row.description
                                : t(':count items', {
                                      count: formatNumber(row.items_count),
                                  }),
                        ),
                    )}
                    phases={phases}
                    editable={editable}
                    fields={{ primary: 'title', secondary: 'description' }}
                />
            </div>
        </>
    );
}

/**
 * Reduce a phase to the row the builder draws.
 *
 * A phase carries `name` where every other kind carries `title`, and files
 * under nothing — it is the thing others are filed under.
 */
function toPhaseRow(phase: DesignPhase): BuilderRow {
    return {
        id: phase.id,
        label: phase.name,
        detail: phase.description,
        position: phase.position,
        phase_id: null,
        status: phase.status,
        status_label: phase.status_label,
    };
}

/**
 * Reduce a piece of content to the row the builder draws.
 */
function toRow(
    content:
        | DesignPrinciple
        | DesignCriterion
        | DesignPractice
        | DesignPrompt
        | Checklist,
    detail: string | null,
): BuilderRow {
    return {
        id: content.id,
        label: content.title,
        detail,
        position: content.position,
        phase_id: content.phase_id,
        status: content.status,
        status_label: content.status_label,
    };
}
