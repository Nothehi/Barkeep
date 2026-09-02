/**
 * The public site.
 *
 * The first of the platform's product areas, and the only one a visitor with
 * no account ever sees. Today that is the landing page; the blog, the guides
 * and the public mechanics list belong here too when they arrive.
 *
 * `resources/js/pages/welcome.tsx` is a thin wrapper over the page component
 * here; Inertia requires page components to live under `pages/`, so the
 * sections live in the feature instead.
 */

export { default as CallToAction } from './components/call-to-action';
export { default as DesignArc } from './components/design-arc';
export { default as DesignLoop } from './components/design-loop';
export { default as FeatureGrid } from './components/feature-grid';
export { default as Hero } from './components/hero';
export { default as LanguageShowcase } from './components/language-showcase';
export { default as SectionHeading } from './components/section-heading';
export { default as SiteFooter } from './components/site-footer';
export { default as SiteHeader } from './components/site-header';
export { default as StudioSection } from './components/studio-section';
export { default as WelcomePage } from './pages/welcome-page';
