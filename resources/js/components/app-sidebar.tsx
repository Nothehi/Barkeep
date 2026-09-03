import { Link } from '@inertiajs/react';
import { Blocks, FolderGit2, Gamepad2, LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useWorkspaces, WorkspaceSwitcher } from '@/features/workspaces';
import { useLocale, useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import games from '@/routes/games';
import mechanics from '@/routes/mechanics';
import type { NavItem } from '@/types';

/**
 * The destinations that mean the same thing wherever you are.
 *
 * There is no entry for workspaces. A workspace is not somewhere you go
 * alongside the rest of the app, it is which app you are in — chosen after
 * signing in and changed from the switcher above these items.
 */
const navItemsFor = (t: (phrase: string) => string): NavItem[] => [
    {
        title: t('Dashboard'),
        href: dashboard.url(),
        icon: LayoutGrid,
    },
];

/**
 * Reference material rather than a place you work, which is why it sits down
 * here instead of in the group above.
 */
const footerNavItemsFor = (t: (phrase: string) => string): NavItem[] => [
    /*
     * Sits beside workspaces rather than inside one, because that is the truth
     * about the domain: a vocabulary is the platform's, not a studio's. Every
     * game picks mechanics from this one list, which is the only thing that
     * makes two games comparable.
     */
    {
        title: t('Mechanics'),
        href: mechanics.index.url(),
        icon: Blocks,
    },
    {
        title: t('Repository'),
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
];

export function AppSidebar() {
    const { current } = useWorkspaces();
    const { t } = useTranslation();
    const { direction } = useLocale();

    const mainNavItems = navItemsFor(t);
    const footerNavItems = footerNavItemsFor(t);

    /**
     * Games hang off a workspace, so the entry only exists once the URL says
     * which one — the same slug the switcher above is showing. Off a
     * workspace there is nowhere for it to point, so it is absent rather than
     * disabled.
     */
    const navItems: NavItem[] = current
        ? [
              ...mainNavItems,
              {
                  title: t('Games'),
                  href: games.index.url(current.slug),
                  icon: Gamepad2,
              },
          ]
        : mainNavItems;

    return (
        /**
         * The rail belongs on the side the reader starts from, so it moves
         * with the language. Everything inside keys off `data-side`, which
         * this prop sets, so the border, the collapse rail and the menu
         * popouts all follow without further work.
         */
        <Sidebar
            collapsible="icon"
            variant="inset"
            side={direction === 'rtl' ? 'right' : 'left'}
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard.url()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>

                <WorkspaceSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
