import { Head, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import type { Game } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import framework from '@/routes/games/framework';
import AdoptionStatusBadge from '../components/adoption-status-badge';
import ChecklistPanel from '../components/checklist-panel';
import CriterionList from '../components/criterion-list';
import PhaseNav from '../components/phase-nav';
import PracticeList from '../components/practice-list';
import PrincipleList from '../components/principle-list';
import ProgressBar from '../components/progress-bar';
import PromptList from '../components/prompt-list';
import type {
    ChecklistProgress,
    CriterionEvaluation,
    DesignCriterion,
    DesignPhase,
    DesignPractice,
    DesignPrinciple,
    DesignPrompt,
    FrameworkProgress,
    GameFramework,
    PracticeCompletion,
    PromptResponse,
    RatingOption,
} from '../types/framework';

type GameFrameworkPhasePageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    adoption: { data: GameFramework };
    progress: { data: FrameworkProgress };
    phase: { data: DesignPhase };
    phases: { data: DesignPhase[] };
    principles: { data: DesignPrinciple[] };
    criteria: { data: DesignCriterion[] };
    practices: { data: DesignPractice[] };
    prompts: { data: DesignPrompt[] };
    evaluations: { data: CriterionEvaluation[] };
    completions: { data: PracticeCompletion[] };
    checklists: { data: ChecklistProgress[] };
    responses: { data: PromptResponse[] };
    options: { ratings: RatingOption[] };
};

/**
 * One phase of a game's framework: the working screen.
 *
 * The busiest page in the module, and the one the module's shape exists for.
 * It shows the methodology's content and this studio's own state side by side
 * — the criteria and this game's grades, the practices and its completions,
 * the checklists and its ticks, the prompts and its answers — while keeping
 * them in separate collections all the way down. A designer sees one page; the
 * data never conflates methodology with progress.
 *
 * The order is deliberate. Principles first, because they are what to hold in
 * mind while doing everything below. Then the criteria, which are the
 * assessment. Then what to do about it.
 */
export default function GameFrameworkPhasePage({
    workspace: { data: workspace },
    game: { data: game },
    adoption: { data: adoption },
    progress: { data: progress },
    phase: { data: phase },
    phases: { data: phases },
    principles: { data: principles },
    criteria: { data: criteria },
    practices: { data: practices },
    prompts: { data: prompts },
    evaluations: { data: evaluations },
    completions: { data: completions },
    checklists: { data: checklists },
    responses: { data: responses },
    options,
}: GameFrameworkPhasePageProps) {
    const canRecord = adoption.permissions.canRecordProgress;
    const here = progress.phase_progress.find(
        (entry) => entry.phase_id === phase.id,
    );

    return (
        <>
            <Head title={`${phase.name} · ${game.name}`} />

            <div className="space-y-6 px-4 py-6">
                <Button size="sm" variant="ghost" asChild className="-ml-2">
                    <Link
                        href={framework.show.url({
                            workspace: workspace.slug,
                            game: game.slug,
                        })}
                    >
                        <ChevronLeft className="size-4" />
                        {game.name}&nbsp;· Framework
                    </Link>
                </Button>

                <header className="space-y-3">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0 space-y-1">
                            <h1 className="truncate text-xl font-semibold tracking-tight">
                                {phase.name}
                            </h1>

                            {phase.description && (
                                <p className="max-w-3xl text-sm text-muted-foreground">
                                    {phase.description}
                                </p>
                            )}
                        </div>

                        <AdoptionStatusBadge
                            status={adoption.status}
                            label={adoption.status_label}
                        />
                    </div>

                    {here && !here.is_empty && (
                        <ProgressBar
                            label="This phase"
                            ratio={here.overall}
                            className="max-w-md"
                        />
                    )}
                </header>

                {/*
                 * Said once, at the top, rather than as a disabled tooltip on
                 * every control below it. Somebody looking at a page of greyed
                 * out checkboxes deserves the reason in one place.
                 */}
                {!canRecord && (
                    <Alert data-test="progress-closed">
                        <AlertTitle>This framework is read-only</AlertTitle>
                        <AlertDescription>
                            {adoption.status === 'paused'
                                ? 'The framework is paused. Resume it to record more work.'
                                : adoption.status === 'completed'
                                  ? 'This game has finished working through its framework. Everything it recorded is still here.'
                                  : 'This game is not accepting changes.'}
                        </AlertDescription>
                    </Alert>
                )}

                {/*
                 * The nav column is 28rem to line its right edge up with the
                 * "This phase" bar above it, which is capped at `max-w-md`. The
                 * two are meant to match: change one and change the other.
                 */}
                <div className="grid gap-6 lg:grid-cols-[28rem_1fr]">
                    {/*
                     * The nav scrolls with the page rather than pinning to it.
                     * A ten phase arc with a wrapping description under each
                     * name is taller than the screen, and a sticky block taller
                     * than its scrollport has to choose between hanging past
                     * the fold over the content below and carrying a scrollbar
                     * of its own. Neither is worth a nav that follows you down.
                     */}
                    <aside>
                        <PhaseNav
                            workspace={workspace.slug}
                            game={game.slug}
                            phases={phases}
                            progress={progress.phase_progress}
                            currentSlug={phase.slug}
                        />
                    </aside>

                    <div className="min-w-0 space-y-8">
                        <PrincipleList principles={principles} />

                        <CriterionList
                            workspace={workspace.slug}
                            game={game.slug}
                            criteria={criteria}
                            evaluations={evaluations}
                            ratings={options.ratings}
                            canRecord={canRecord}
                        />

                        <PracticeList
                            workspace={workspace.slug}
                            game={game.slug}
                            practices={practices}
                            completions={completions}
                            canRecord={canRecord}
                        />

                        <ChecklistPanel
                            workspace={workspace.slug}
                            game={game.slug}
                            checklists={checklists}
                            canRecord={canRecord}
                        />

                        <PromptList
                            workspace={workspace.slug}
                            game={game.slug}
                            prompts={prompts}
                            responses={responses}
                            canRecord={canRecord}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
