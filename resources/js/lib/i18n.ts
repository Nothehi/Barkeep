import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

/**
 * A phrase catalogue, keyed by its English source text.
 *
 * The key *is* the English phrase, exactly as Laravel's JSON translation
 * files work and exactly as `__('Start designing')` reads on the server. That
 * choice is what lets one `lang/fa.json` serve both sides: a string written in
 * a controller and the same string written in a React component resolve
 * through the same entry, and neither side can drift onto a key the other
 * does not know about.
 *
 * English therefore has no catalogue. A missing translation falls back to the
 * key, which is already the sentence we want to show.
 */
export type TranslationCatalogue = Record<string, string>;

export type Replacements = Record<string, string | number>;

export type LocaleDirection = 'ltr' | 'rtl';

export type SupportedLocale = {
    readonly code: string;
    readonly name: string;
    readonly native: string;
    readonly direction: LocaleDirection;
};

export type LocaleState = {
    readonly current: string;
    readonly direction: LocaleDirection;
    readonly supported: readonly SupportedLocale[];
};

const LOCALE_COOKIE = 'locale';

/**
 * Apply Laravel's `:placeholder` substitution to a resolved phrase.
 *
 * The casing rules are Laravel's, not an approximation of them: `:name` takes
 * the value as given, `:Name` upper-cases the first letter and `:NAME`
 * upper-cases the lot. Translators rely on this to start a sentence with a
 * value that arrives lower-cased, so dropping it would silently mis-render
 * strings that already work on the server.
 *
 * Longer placeholders are substituted first so `:game` cannot eat the front of
 * `:gameName` and leave `Name` stranded in the output.
 */
function replacePlaceholders(
    phrase: string,
    replacements: Replacements,
): string {
    const keys = Object.keys(replacements).sort((a, b) => b.length - a.length);

    return keys.reduce((carry, key) => {
        const value = String(replacements[key]);

        return carry
            .replaceAll(`:${key.toUpperCase()}`, value.toUpperCase())
            .replaceAll(
                `:${key.charAt(0).toUpperCase()}${key.slice(1)}`,
                value.charAt(0).toUpperCase() + value.slice(1),
            )
            .replaceAll(`:${key}`, value);
    }, phrase);
}

/**
 * Pick the right side of a `singular|plural` phrase for a count.
 *
 * This is the plain form of Laravel's `trans_choice`. It is deliberately not
 * the full range-notation implementation — nothing in this application needs
 * `{0} none|[1,19] some|[20,*] many`, and a language whose catalogue supplies
 * one form for both cases (Persian does not inflect a noun after a numeral)
 * simply writes no separator and gets that form back for every count.
 */
function selectPluralForm(phrase: string, count: number): string {
    const forms = phrase.split('|');

    if (forms.length === 1) {
        return phrase;
    }

    return count === 1 ? forms[0] : forms[forms.length - 1];
}

/**
 * Translate a phrase against a catalogue.
 *
 * Exported separately from the hook so the same resolution is available to
 * code that has a catalogue but no React context to read it from — page title
 * callbacks, mostly.
 */
export function translate(
    catalogue: TranslationCatalogue,
    phrase: string,
    replacements: Replacements = {},
): string {
    return replacePlaceholders(catalogue[phrase] ?? phrase, replacements);
}

/**
 * Translate a phrase, substituting any `:placeholder` values.
 *
 * `undefined` passes straight through rather than being rejected, because so
 * many of the things worth translating are optional props — a card's
 * description, a layout's subtitle. Without this every such call site would
 * have to guard, and the guard would read as though the absence mattered.
 */
type Translate = {
    (phrase: string, replacements?: Replacements): string;
    (phrase: undefined, replacements?: Replacements): undefined;
    (
        phrase: string | undefined,
        replacements?: Replacements,
    ): string | undefined;
};

type UseTranslationReturn = {
    readonly t: Translate;

    /**
     * Translate a `singular|plural` phrase for a count.
     *
     * The count is available to the phrase as `:count` without being passed
     * again, matching `trans_choice`.
     */
    readonly choice: (
        phrase: string,
        count: number,
        replacements?: Replacements,
    ) => string;
};

/**
 * Read the active locale and everything the switcher needs to change it.
 */
export function useLocale(): LocaleState {
    return usePage().props.locale;
}

/**
 * The translator for the active locale.
 */
export function useTranslation(): UseTranslationReturn {
    const catalogue = usePage().props.translations;

    const t = useCallback(
        (
            phrase: string | undefined,
            replacements: Replacements = {},
        ): string | undefined =>
            phrase === undefined
                ? undefined
                : translate(catalogue, phrase, replacements),
        [catalogue],
    ) as Translate;

    const choice = useCallback(
        (
            phrase: string,
            count: number,
            replacements: Replacements = {},
        ): string =>
            replacePlaceholders(
                selectPluralForm(catalogue[phrase] ?? phrase, count),
                { count, ...replacements },
            ),
        [catalogue],
    );

    return { t, choice };
}

/**
 * Switch the interface language.
 *
 * The choice is kept in the same unencrypted cookie the server reads, so the
 * next full page load already knows the answer. `lang` and `dir` are written
 * onto the document here as well as by the root template, because the reload
 * below is an Inertia visit rather than a browser navigation — the server
 * re-renders the page but not the `<html>` element around it.
 */
export function useSetLocale(): (code: string) => void {
    const { current, supported } = useLocale();

    return useCallback(
        (code: string): void => {
            if (code === current) {
                return;
            }

            const target = supported.find((locale) => locale.code === code);

            if (!target) {
                return;
            }

            const maxAge = 365 * 24 * 60 * 60;

            document.cookie = `${LOCALE_COOKIE}=${code};path=/;max-age=${maxAge};SameSite=Lax`;

            document.documentElement.lang = code;
            document.documentElement.dir = target.direction;

            /**
             * The catalogue is a once prop keyed by locale, so this reload is
             * what fetches the new one: the client no longer recognises the
             * key it has remembered and the server sends the replacement.
             */
            router.reload();
        },
        [current, supported],
    );
}

type UseFormattersReturn = {
    readonly formatDate: (value: string | Date) => string;
    readonly formatDateTime: (value: string | Date) => string;
    readonly formatTime: (value: string | Date) => string;
    readonly formatNumber: (value: number) => string;
};

/**
 * Date and number formatting bound to the active locale.
 *
 * Worth routing through here rather than calling `toLocaleDateString()` with
 * no arguments: that formats in the *browser's* language, so somebody reading
 * the interface in Persian on an English-configured machine would get Persian
 * labels beside Gregorian dates in English month names. Asking `Intl` for
 * `fa` gets the Persian calendar and Persian digits, which is what the rest of
 * the page has just promised.
 */
export function useFormatters(): UseFormattersReturn {
    const { current } = useLocale();

    return useMemo(() => {
        const date = new Intl.DateTimeFormat(current, { dateStyle: 'medium' });
        const dateTime = new Intl.DateTimeFormat(current, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
        const time = new Intl.DateTimeFormat(current, { timeStyle: 'short' });
        const number = new Intl.NumberFormat(current);

        const asDate = (value: string | Date): Date =>
            value instanceof Date ? value : new Date(value);

        return {
            formatDate: (value) => date.format(asDate(value)),
            formatDateTime: (value) => dateTime.format(asDate(value)),
            formatTime: (value) => time.format(asDate(value)),
            formatNumber: (value) => number.format(value),
        };
    }, [current]);
}
