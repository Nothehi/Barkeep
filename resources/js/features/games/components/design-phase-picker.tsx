import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { changeDesignPhase } from '../api';
import { useGamePermissions } from '../hooks/use-game-permissions';
import type { DesignPhase, Game, GameOptions } from '../types/game';

type DesignPhasePickerProps = {
    game: Game;
    workspace: string;
    options: GameOptions['design_phases'];
};

/**
 * Moves a game through the design process.
 *
 * A free choice, unlike the lifecycle actions beside it, and that difference
 * is the point. Designing a board game is not a pipeline: a game that reaches
 * playtesting and turns out to have a broken core loop goes back to
 * prototyping, and an interface that only offered "next phase" would describe
 * a process nobody follows.
 */
export default function DesignPhasePicker({
    game,
    workspace,
    options,
}: DesignPhasePickerProps) {
    const permissions = useGamePermissions(game);
    const [processing, setProcessing] = useState(false);

    const change = (value: string) => {
        setProcessing(true);

        changeDesignPhase(workspace, game.slug, value as DesignPhase, {
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Select
            value={game.design_phase}
            onValueChange={change}
            disabled={!permissions.canChangeDesignPhase || processing}
        >
            <SelectTrigger className="w-full" data-test="design-phase-picker">
                <SelectValue />
            </SelectTrigger>

            <SelectContent>
                {options.map((phase) => (
                    <SelectItem key={phase.value} value={phase.value}>
                        <span className="flex flex-col items-start">
                            <span>{phase.label}</span>
                            <span className="text-xs text-muted-foreground">
                                {phase.description}
                            </span>
                        </span>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
