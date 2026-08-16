import { Link } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    Gamepad2,
    LayoutGrid,
    Boxes,
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
import games from '@/routes/games';
import workspaces from '@/routes/workspaces';
import type { NavItem } from '@/types';

/**
 * The destinations that mean the same thing wherever you are.
 */
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Workspaces',
        href: workspaces.index(),
        icon: Boxes,
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
