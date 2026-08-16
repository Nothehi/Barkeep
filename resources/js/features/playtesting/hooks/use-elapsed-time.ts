import { useEffect, useState } from 'react';

/**
 * How long a running session has been going, ticking once a second.
 *
 * Derived from the server's `started_at` rather than counted up from when the
 * page loaded, so refreshing mid-session does not restart the clock and two
 * people watching the same session see the same number.
 *
 * Returns null when there is nothing to count — a session that has not started
 * or has already ended has a duration the server can state exactly, and this
 * has no business guessing at one.
 */
export function useElapsedTime(startedAt: string | null): number | null {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (startedAt === null) {
            return;
        }

        const tick = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(tick);
    }, [startedAt]);

    if (startedAt === null) {
        return null;
    }

    const elapsed = Math.floor((now - Date.parse(startedAt)) / 1000);

    return elapsed < 0 ? 0 : elapsed;
}

/**
 * Write a number of seconds as a running clock: 01:24:32.
 *
 * Deliberately different from the server's "1h 15m" wording, because the two
 * are read differently. A finished session is summarised; a running one is
 * watched, and a clock that does not show seconds looks broken.
 */
export function formatElapsed(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainder = seconds % 60;

    return [hours, minutes, remainder]
        .map((part) => part.toString().padStart(2, '0'))
        .join(':');
}
