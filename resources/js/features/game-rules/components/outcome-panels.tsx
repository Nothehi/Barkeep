import { useTranslation } from '@/lib/i18n';
import {
    createDefeatCondition,
    createGameEndCondition,
    createVictoryCondition,
    deleteDefeatCondition,
    deleteGameEndCondition,
    deleteVictoryCondition,
} from '../api';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import type { Outcome, RuleCondition } from '../types/game-rules';
import OutcomeEditor from './outcome-editor';

type PanelProps = {
    outcomes: Outcome[];
    conditions: RuleCondition[];
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * The three outcome panels.
 *
 * Named separately rather than passed a `kind`, because that is what they are on the server: winning, losing
 * and stopping are three different questions, and a game routinely answers all three at once. The round
 * eight marker ends it, the highest score wins it, and a player at zero health lost it two rounds ago.
 *
 * Each is a thin wrapper over one editor. What differs is the wording and which endpoint it calls.
 */

export function VictoryConditionEditor({
    outcomes,
    conditions,
    scope,
    canEdit,
}: PanelProps) {
    const { t } = useTranslation();

    return (
        <OutcomeEditor
            heading={t('Victory conditions')}
            addLabel={t('Add a way to win')}
            emptyMessage={t('Nobody can win yet.')}
            placeholder={t('First player to reach 20 victory points.')}
            outcomes={outcomes}
            conditions={conditions}
            scope={scope}
            canEdit={canEdit}
            create={createVictoryCondition}
            remove={(id) =>
                deleteVictoryCondition({ ...scope, victoryCondition: id })
            }
        />
    );
}

export function DefeatConditionEditor({
    outcomes,
    conditions,
    scope,
    canEdit,
}: PanelProps) {
    const { t } = useTranslation();

    return (
        <OutcomeEditor
            heading={t('Defeat conditions')}
            addLabel={t('Add a way to be knocked out')}
            emptyMessage={t(
                'Nobody can be knocked out. That may be deliberate.',
            )}
            placeholder={t('Your health reaches zero.')}
            outcomes={outcomes}
            conditions={conditions}
            scope={scope}
            canEdit={canEdit}
            create={createDefeatCondition}
            remove={(id) =>
                deleteDefeatCondition({ ...scope, defeatCondition: id })
            }
        />
    );
}

export function GameEndConditionEditor({
    outcomes,
    conditions,
    scope,
    canEdit,
}: PanelProps) {
    const { t } = useTranslation();

    return (
        <OutcomeEditor
            heading={t('Game end conditions')}
            addLabel={t('Add a way for the game to end')}
            emptyMessage={t('Nothing stops the game.')}
            placeholder={t('The main deck runs out.')}
            outcomes={outcomes}
            conditions={conditions}
            scope={scope}
            canEdit={canEdit}
            create={createGameEndCondition}
            remove={(id) =>
                deleteGameEndCondition({ ...scope, endCondition: id })
            }
        />
    );
}
