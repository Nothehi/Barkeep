import { Compass } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { adoptFramework } from '../api';
import type { Framework } from '../types/framework';

type AdoptFrameworkPanelProps = {
    workspace: string;
    game: string;
    frameworks: Framework[];
    canAssign: boolean;
};

/**
 * The choice a game makes once.
 *
 * Shown in place of the framework screen when a game follows nothing yet,
 * rather than on a page of its own — adopting is what this screen offers when
 * there is nothing to show, and giving it its own destination would leave a
 * dead link on every game that already has one.
 *
 * Only the latest published edition of each methodology is offered. Adopting
 * an older one is a real thing an author might want and not a thing a designer
 * choosing a process wants to think about, and the server would refuse a draft
 * anyway.
 *
 * There is no way back from this choice inside the module. Changing a game's
 * edition is migration — which has real decisions in it about what happens to
 * evaluations already recorded — so the panel says so before the button is
 * pressed rather than after.
 */
export default function AdoptFrameworkPanel({
    workspace,
    game,
    frameworks,
    canAssign,
}: AdoptFrameworkPanelProps) {
    const { t } = useTranslation();
    const [pending, setPending] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const adoptable = frameworks.filter(
        (framework) => framework.latest_version?.is_adoptable ?? false,
    );

    if (adoptable.length === 0) {
        return (
            <div
                className="flex flex-col items-center gap-2 rounded-lg border border-dashed px-6 py-12 text-center"
                data-test="adopt-framework-empty"
            >
                <Compass className="size-6 text-muted-foreground" />

                <p className="text-sm font-medium">
                    {t('No frameworks are available yet')}
                </p>

                <p className="max-w-md text-sm text-muted-foreground">
                    {t(
                        'A methodology has to have a published edition before a game can follow it.',
                    )}
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-4" data-test="adopt-framework-panel">
            <div className="space-y-1">
                <h2 className="text-sm font-medium">
                    {t('Choose a framework')}
                </h2>

                <p className="text-sm text-muted-foreground">
                    {t(
                        'This game will follow the edition you pick for as long as it exists — later editions will not move it, so its answers stay attached to the questions that were actually asked.',
                    )}
                </p>
            </div>

            <InputError message={errors.framework_version_id} />

            <div className="grid gap-3 md:grid-cols-2">
                {adoptable.map((framework) => {
                    const edition = framework.latest_version!;

                    return (
                        <Card key={framework.id}>
                            <CardHeader className="gap-1">
                                <span className="font-medium" dir="auto">
                                    {framework.name}
                                </span>

                                <span className="text-xs text-muted-foreground">
                                    {edition.label}
                                    {edition.name ? ` · ${edition.name}` : ''}
                                </span>
                            </CardHeader>

                            <CardContent className="space-y-3">
                                {framework.description && (
                                    <p
                                        className="line-clamp-3 text-sm text-muted-foreground"
                                        dir="auto"
                                    >
                                        {framework.description}
                                    </p>
                                )}

                                {canAssign && (
                                    <Button
                                        size="sm"
                                        disabled={pending !== null}
                                        onClick={() => {
                                            setPending(edition.id);
                                            setErrors({});

                                            adoptFramework(
                                                workspace,
                                                game,
                                                edition.id,
                                                {
                                                    onError: setErrors,
                                                    onFinish: () =>
                                                        setPending(null),
                                                },
                                            );
                                        }}
                                        data-test={`adopt-${framework.slug}`}
                                    >
                                        {pending === edition.id && <Spinner />}
                                        {t('Follow :edition', {
                                            edition: edition.label,
                                        })}
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        </div>
    );
}
