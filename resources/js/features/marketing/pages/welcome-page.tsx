import { Head } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import CallToAction from '../components/call-to-action';
import DesignLoop from '../components/design-loop';
import FeatureGrid from '../components/feature-grid';
import Hero from '../components/hero';
import LanguageShowcase from '../components/language-showcase';
import SiteFooter from '../components/site-footer';
import SiteHeader from '../components/site-header';
import StudioSection from '../components/studio-section';

/**
 * The landing page, and the only screen in the application a visitor can
 * reach without an account.
 *
 * The order is an argument rather than a feature list: what the product is
 * for, the loop it puts you in, what that loop leaves behind, who it belongs
 * to, and the language it is read in. Each section is its own component so
 * that reading this file tells you the shape of the argument.
 *
 * `app.tsx` gives this page no layout — the signed-in shell would put a
 * sidebar and a workspace switcher around a page whose whole job is to
 * explain why either would be worth having.
 */
export default function WelcomePage() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Board-game design, made executable')} />

            <div className="flex min-h-svh flex-col bg-background text-foreground">
                <a
                    href="#main"
                    className="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:text-primary-foreground"
                >
                    {t('Skip to content')}
                </a>

                <SiteHeader />

                <main id="main" className="flex-1">
                    <Hero />
                    <DesignLoop />
                    <FeatureGrid />
                    <StudioSection />
                    <LanguageShowcase />
                    <CallToAction />
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
