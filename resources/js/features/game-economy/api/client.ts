import { http } from '@inertiajs/react';

/**
 * The small JSON client the balance read functions share.
 *
 * Built on Inertia's own HTTP client rather than on `fetch`, so requests inherit the session cookie, the
 * CSRF header and any interceptors the application has registered — the same plumbing an Inertia visit gets.
 *
 * These functions exist for reads. Anything that writes should use an Inertia visit instead, so the server
 * can redirect and flash on success — see `./mutation` for why that matters more in this module than in
 * most.
 */

type RequestOptions = {
    method: 'get' | 'post' | 'patch' | 'delete';
    url: string;
    data?: unknown;
    signal?: AbortSignal;
};

/**
 * A request that reached the server and came back with a failing status.
 *
 * Carries the status so callers can tell "you may not" (403) from "there is no such thing" (404) from a rule
 * violation (409/422) without parsing the message. An archived profile answers 403; one in somebody else's
 * workspace answers 404; a resource eleven actions are priced in answers 409.
 */
export class BalanceApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
    ) {
        super(message);
        this.name = 'BalanceApiError';
    }
}

/**
 * Perform a request against the balance API and return its payload.
 *
 * @throws {BalanceApiError} when the server answers with a failing status.
 */
export async function request<T>({
    method,
    url,
    data,
    signal,
}: RequestOptions): Promise<T> {
    const response = await http.getClient().request({
        method,
        url,
        data,
        signal,
        headers: { Accept: 'application/json' },
    });

    const payload = parse(response.data);

    if (response.status >= 400) {
        throw new BalanceApiError(response.status, messageFrom(payload));
    }

    return payload as T;
}

/**
 * Unwrap the `data` envelope API Resources put around every response.
 */
export function unwrap<T>(payload: { data: T }): T {
    return payload.data;
}

/**
 * The client hands back a string for some transports and parsed JSON for others, so normalise both into a
 * value.
 */
function parse(data: unknown): unknown {
    if (typeof data !== 'string') {
        return data;
    }

    if (data === '') {
        return null;
    }

    try {
        return JSON.parse(data);
    } catch {
        return data;
    }
}

function messageFrom(payload: unknown): string {
    if (
        typeof payload === 'object' &&
        payload !== null &&
        'message' in payload &&
        typeof payload.message === 'string'
    ) {
        return payload.message;
    }

    return 'The request could not be completed.';
}
