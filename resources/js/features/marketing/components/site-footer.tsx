import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggleTab from '@/components/appearance-tabs';
import LanguageSwitcher from '@/components/language-switcher';
import { useLocale, useTranslation } from '@/lib/i18n';

/**
 * The public site's footer.
 *
 * It carries the two settings a visitor might want before they have an
 * account to keep them in — the language and the theme — because the only
 * other place they live is behind a sign in.
 *
 * The year is formatted through `Intl` rather than interpolated: a Persian
 * reader is shown the Persian year, which is the one the rest of the page has
 * just promised them.
 */
export default function SiteFooter() {
    const { name } = usePage().props;
    const { t } = useTranslation();
    const { current } = useLocale();

    const year = new Intl.DateTimeFormat(current, { year: 'numeric' }).format(
        new Date(),
    );

    return (
        <footer className="border-t">
            <div className="mx-auto flex w-full max-w-6xl flex-col items-center gap-8 px-6 py-12 md:flex-row md:items-start md:justify-between">
                <div className="text-center md:text-start">
                    <p className="flex items-center justify-center gap-2.5 font-semibold md:justify-start">
                        <AppLogoIcon className="size-5 shrink-0 fill-current" />
                        <span dir="auto">{name}</span>
                    </p>

                    <p className="mt-2 text-sm text-muted-foreground">
                        {t('A workflow for designing board games.')}
                    </p>

                    <p className="mt-4 text-xs text-muted-foreground tabular-nums">
                        <span>© {year}</span> <span dir="auto">{name}</span>
                    </p>
                </div>

                <div className="flex flex-col items-center gap-3 sm:flex-row">
                    <AppearanceToggleTab />
                    <LanguageSwitcher />
                </div>
            </div>
        </footer>
    );
}
