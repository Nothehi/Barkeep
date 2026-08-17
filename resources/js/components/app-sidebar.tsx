import { Link } from '@inertiajs/react';
import {
    Blocks,
    BookOpen,
    FolderGit2,
    Gamepad2,
    LayoutGrid,
    Library,
} from 'lucide-react';
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
import { dashboard } from '@/routes';
import frameworks from '@/routes/frameworks';
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
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    /*
     * Sits beside workspaces rather than inside one, because that is the truth
     * about the domain: a methodology is the platform's, not a studio's. The
     * catalogue is readable by everybody; only the accounts configured to
     * administer frameworks can write one, and the screen says so itself.
     */
    {
        title: 'Frameworks',
        href: frameworks.index(),
        icon: Library,
    },
    /*
     * Beside frameworks for the same reason, and it is the same reason twice:
     * a methodology and a vocabulary are both the platform's rather than a
     * studio's. Every game picks mechanics from this one list, which is the
     * only thing that makes two games comparable.
     */
    {
        title: 'Mechanics',
        href: mechanics.index(),
        icon: Blocks,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { current } = useWorkspaces();

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
                  title: 'Games',
                  href: games.index(current.slug),
                  icon: Gamepad2,
              },
          ]
        : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
