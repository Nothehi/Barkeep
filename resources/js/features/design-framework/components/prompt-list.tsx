import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { respondToPrompt } from '../api';
import type { DesignPrompt, PromptResponse } from '../types/framework';

type PromptListProps = {
    workspace: string;
    game: string;
    prompts: DesignPrompt[];
    responses: PromptResponse[];
    canRecord: boolean;
};

/**
 * The open questions this phase asks, and what this game has written.
 *
 * Answering again overwrites, and the interface says so rather than hiding it:
 * a prompt asks what the design is now, and no history is kept. The box is
 * generous because this is where a designer writes down what their game is
 * actually about.
 *
 * Nothing here moves a progress bar. Prompts are counted and reported and
 * deliberately excluded from the total — a prompt has no right answer, so
 * letting it advance a percentage would reward typing over thinking.
 */
export default function PromptList({
    workspace,
    game,
    prompts,
    responses,
    canRecord,
}: PromptListProps) {
    const { t } = useTranslation();

    const byPrompt = new Map(
        responses.map((response) => [response.prompt_id, response]),
    );

    if (prompts.length === 0) {
        return null;
    }

    return (
        <section className="space-y-3" data-test="prompt-list">
            <h2 className="text-sm font-medium">{t('Questions to answer')}</h2>

            <div className="grid gap-3">
                {prompts.map((prompt) => (
                    <PromptRow
                        key={prompt.id}
                        workspace={workspace}
                        game={game}
                        prompt={prompt}
                        response={byPrompt.get(prompt.id) ?? null}
                        canRecord={canRecord}
                    />
                ))}
            </div>
        </section>
    );
}

type PromptRowProps = {
    workspace: string;
    game: string;
    prompt: DesignPrompt;
    response: PromptResponse | null;
    canRecord: boolean;
};

function PromptRow({
    workspace,
    game,
    prompt,
    response,
    canRecord,
}: PromptRowProps) {
    const { t } = useTranslation();
    const [draft, setDraft] = useState(response?.response ?? '');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const unsaved = draft.trim() !== (response?.response ?? '').trim();

    return (
        <Card data-test={`prompt-${prompt.id}`}>
            <CardHeader className="gap-1">
                <span className="font-medium" dir="auto">
                    {prompt.title}
                </span>

                <span className="text-sm text-muted-foreground" dir="auto">
                    {prompt.prompt}
                </span>
            </CardHeader>

            <CardContent className="space-y-2">
                {canRecord ? (
                    <>
                        <Textarea
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            rows={5}
                            placeholder={t(
                                'Write what is true of this game today.',
                            )}
                            data-test={`prompt-input-${prompt.id}`}
                        />

                        {errors.response && (
                            <p
                                className="text-sm text-red-600 dark:text-red-400"
                                dir="auto"
                            >
                                {errors.response}
                            </p>
                        )}

                        <div className="flex flex-wrap items-center gap-3">
                            <Button
                                size="sm"
                                disabled={
                                    processing ||
                                    !unsaved ||
                                    draft.trim() === ''
                                }
                                onClick={() => {
                                    setProcessing(true);
                                    setErrors({});

                                    respondToPrompt(
                                        workspace,
                                        game,
                                        prompt.id,
                                        draft,
                                        {
                                            onError: setErrors,
                                            onFinish: () =>
                                                setProcessing(false),
                                        },
                                    );
                                }}
                                data-test={`prompt-save-${prompt.id}`}
                            >
                                {processing && <Spinner />}
                                {response === null
                                    ? t('Answer')
                                    : t('Replace answer')}
                            </Button>

                            {response !== null && !unsaved && (
                                <span className="text-xs text-muted-foreground">
                                    {response.was_revised
                                        ? t('Answered, and revised since.')
                                        : t('Answered.')}
                                </span>
                            )}
                        </div>
                    </>
                ) : response === null ? (
                    <p className="text-sm text-muted-foreground">
                        {t('Not answered yet.')}
                    </p>
                ) : (
                    <p className="text-sm whitespace-pre-line" dir="auto">
                        {response.response}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
