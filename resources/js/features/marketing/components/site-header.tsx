import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitcher from '@/components/language-switcher';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, home, login, register } from '@/routes';

/**
 * The public site's header.
 *
 * Not `AppHeader`: that one belongs to the signed-in shell and carries
 * breadcrumbs, a workspace switcher and an account menu, none of which mean
 * anything to somebody who has just arrived. What a visitor needs is the way
 * into the page's own sections and the way into the application.
 *
 * The language switcher sits here for the same reason it sits on the auth
 * screens — a reader of Persian has to be able to switch before there is an
 * account to hold the preference.
 */
export default function SiteHeader() {
    const { auth, name } = usePage().props;
    const { t } = useTranslation();

    /**
     * Built inside the component: the labels are translated, and a module
     * constant would be evaluated once, before any locale is known.
     */
    const sections = [
        { href: '#how-it-works', label: t('How it works') },
        { href: '#workspace', label: t('The workspace') },
        { href: '#languages', label: t('Two directions') },
    ];

    return (
        <header className="sticky top-0 z-50 border-b bg-background/85 backdrop-blur">
            <div className="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-6">
                <Link
                    href={home()}
                    className="flex items-center gap-2.5 font-semibold"
                >
                    <AppLogoIcon className="size-6 shrink-0 fill-current" />
                    <span dir="auto">{name}</span>
                </Link>

                <nav
                    aria-label={t('Navigation menu')}
                    className="hidden flex-1 items-center justify-center gap-1 md:flex"
                >
                    {sections.map((section) => (
                        <a
                            key={section.href}
                            href={section.href}
                            className="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {section.label}
                        </a>
                    ))}
                </nav>

                <div className="ms-auto flex items-center gap-1 md:ms-0">
                    <LanguageSwitcher />

                    {auth?.user ? (
                        <Button size="sm" asChild>
                            <Link href={dashboard()}>{t('Dashboard')}</Link>
                        </Button>
                    ) : (
                        <>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="hidden sm:inline-flex"
                                asChild
                            >
                                <Link href={login()}>{t('Log in')}</Link>
                            </Button>

                            <Button size="sm" asChild>
                                <Link href={register()}>
                                    {t('Start designing')}
                                </Link>
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
