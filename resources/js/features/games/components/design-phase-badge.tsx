import { Compass } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { DesignPhase } from '../types/game';

type DesignPhaseBadgeProps = {
    phase: DesignPhase;
    label?: string;
};

const FALLBACK_LABEL: Record<DesignPhase, string> = {
    idea: 'Idea',
    concept: 'Concept',
    core_design: 'Core design',
    prototyping: 'Prototyping',
    playtesting: 'Playtesting',
    development: 'Development',
    production: 'Production',
    published: 'Published',
};

/**
 * How far a game has got in the design process.
 *
 * Drawn in one neutral tone on purpose. A phase is a position, not a verdict —
 * a game at "idea" is not doing worse than one at "production", it is just
 * earlier, and colouring them differently would suggest otherwise.
 */
export default function DesignPhaseBadge({
    phase,
    label,
}: DesignPhaseBadgeProps) {
    return (
        <Badge variant="outline" data-test={`design-phase-${phase}`}>
            <Compass className="size-3" />
            {label ?? FALLBACK_LABEL[phase]}
        </Badge>
    );
}
