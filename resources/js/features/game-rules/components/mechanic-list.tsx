import { Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import { deleteMechanic } from '../api';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import type { RuleMechanic, RuleOptions } from '../types/game-rules';
import MechanicForm from './mechanic-form';

type MechanicListProps = {
    mechanics: RuleMechanic[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * The mechanisms this rule system says it uses.
 *
 * Read top to bottom, this is the elevator pitch for the game: worker placement, set collection, push your
 * luck. Which is why the list keeps the designer's own order rather than sorting alphabetically — the first
 * three are the ones that describe the game.
 */
export default function MechanicList({
    mechanics,
    options,
    scope,
    canEdit,
}: MechanicListProps) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Mechanics')}</CardTitle>

                {canEdit && <MechanicForm scope={scope} options={options} />}
            </CardHeader>

            <CardContent>
                {mechanics.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t('No mechanisms named yet.')}
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {mechanics.map((mechanic) => (
                            <li
                                key={mechanic.id}
                                className="flex flex-wrap items-start gap-2 rounded-md border px-3 py-2"
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span
                                            className="text-sm font-medium"
                                            dir="auto"
                                        >
                                            {mechanic.name}
                                        </span>

                                        <Badge variant="outline">
                                            {mechanic.category_label}
                                        </Badge>
                                    </div>

                                    {mechanic.description && (
                                        <p
                                            className="text-sm text-muted-foreground"
                                            dir="auto"
                                        >
                                            {mechanic.description}
                                        </p>
                                    )}
                                </div>

                                {canEdit && (
                                    <div className="ms-auto flex items-center gap-1">
                                        <MechanicForm
                                            scope={scope}
                                            options={options}
                                            mechanic={mechanic}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t(
                                                        'Edit mechanic',
                                                    )}
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                            }
                                        />

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Remove mechanic')}
                                            onClick={() =>
                                                deleteMechanic({
                                                    ...scope,
                                                    ruleMechanic: mechanic.id,
                                                })
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
