import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitcher from '@/components/language-switcher';
import { useTranslation } from '@/lib/i18n';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * The heading and blurb arrive as layout props declared at module scope on
 * each auth page, which is somewhere a hook cannot run. They are translated
 * here instead, which works for the same reason breadcrumbs do: the
 * catalogue is keyed by the English sentence.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { t } = useTranslation();

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            {/*
             * Signing in and registering happen before there is an account to
             * hold a preference, so the choice has to be reachable from the
             * auth screens themselves rather than only from settings.
             */}
            <LanguageSwitcher className="absolute end-4 top-4" />

            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                            </div>
                            <span className="sr-only">{t(title)}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{t(title)}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {t(description)}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
