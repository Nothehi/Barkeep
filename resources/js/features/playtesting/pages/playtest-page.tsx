import { Head } from '@inertiajs/react';
import type { Game } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import PlaytestHeader from '../components/playtest-header';
import PlaytestSummary from '../components/playtest-summary';
import SessionList from '../components/session-list';
import type {
    Playtest,
    PlaytestMetrics,
    PlaytestSession,
} from '../types/playtest';

type PlaytestPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    playtest: { data: Playtest };
    sessions: { data: PlaytestSession[] };
    summary: { data: PlaytestMetrics };
};

/**
 * One playtest: the question, the evidence, and the sittings that produced it.
 *
 * Ordered to make the testing loop legible top to bottom — what was being
 * asked, what was expected, what has been found, and where it was found. A
 * designer coming back to this months later should be able to read it as an
 * argument rather than as a pile of records.
 *
 * The conclusion appears at the end and only once there is one, because it is
 * the answer: showing an empty "conclusion" heading above the evidence would
 * put the punchline before the story.
 */
export default function PlaytestPage({
    workspace: { data: workspace },
    game: { data: game },
    playtest: { data: playtest },
    sessions: { data: sessions },
    summary: { data: summary },
}: PlaytestPageProps) {
    return (
        <>
            <Head title={`${playtest.title} · ${game.name}`} />

            <div className="space-y-8 px-4 py-6">
                <PlaytestHeader
                    playtest={playtest}
                    workspace={workspace.slug}
                    game={game.slug}
                />

                <section className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-1.5">
                        <h2 className="text-sm font-semibold text-muted-foreground">
                            Objective
                        </h2>

                        <p className="text-sm whitespace-pre-line">
                            {playtest.objective}
                        </p>
                    </div>

                    <div className="space-y-1.5">
                        <h2 className="text-sm font-semibold text-muted-foreground">
                            Hypothesis
                        </h2>

                        <p className="text-sm whitespace-pre-line text-muted-foreground">
                            {playtest.hypothesis ??
                                'None stated — exploratory.'}
                        </p>
                    </div>
                </section>

                <PlaytestSummary summary={summary} />

                <SessionList
                    playtest={playtest}
                    sessions={sessions}
                    workspace={workspace.slug}
                    game={game.slug}
                />

                {playtest.conclusion && (
                    <section
                        className="space-y-1.5 rounded-lg border bg-muted/40 p-4"
                        data-test="playtest-conclusion"
                    >
                        <h2 className="text-sm font-semibold">Conclusion</h2>

                        <p className="text-sm whitespace-pre-line">
                            {playtest.conclusion}
                        </p>
                    </section>
                )}
            </div>
        </>
    );
}
