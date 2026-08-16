import { usePage } from '@inertiajs/react';
import type { Game } from '../types/game';

/**
 * The game the current screen is about.
 *
 * Read from the page's own `game` prop, which every game screen sends in
 * full. Screens that are not about one game get null.
 */
export function useGame(): Game | null {
    const page = usePage<{ game?: { data: Game } | Game }>();
    const fromPage = page.props.game;

    if (!fromPage) {
        return null;
    }

    return 'data' in fromPage ? fromPage.data : fromPage;
}
