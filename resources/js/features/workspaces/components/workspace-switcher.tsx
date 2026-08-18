import { Link, router } from '@inertiajs/react';
import {
    Check,
    ChevronsUpDown,
    LayoutGrid,
    Plus,
    Settings2,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { useLocale, useTranslation } from '@/lib/i18n';
import workspaces from '@/routes/workspaces';
import { useWorkspaces } from '../hooks/use-workspaces';

/**
 * Moves between the workspaces the account belongs to.
 *
 * The list comes from the server's shared props, already scoped to
 * membership, so the switcher cannot offer somewhere the account does not
 * belong. Switching posts the choice rather than navigating to it: it changes
 * the workspace the account is working in, which is the same choice the step
 * after sign in asks for, and it has to outlast the page it was made on.
 *
 * No authorization decision is made here. The server authorizes the workspace
 * against the policy before it remembers the choice, and every request after
 * that is authorized against the workspace its own URL resolves to.
 */
export default function WorkspaceSwitcher() {
    const { workspaces: available, current, hasWorkspaces } = useWorkspaces();
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const { t } = useTranslation();
    const { direction } = useLocale();

    if (!hasWorkspaces) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        asChild
                        size="lg"
                        /**
                         * `size="lg"` drops the button's padding when the rail
                         * collapses, which is right for the logo and the
                         * avatar — both fill the icon box themselves. This
                         * button leads with a bare icon, so the padding has to
                         * come back or the icon sits against the start edge
                         * instead of in the middle of its box.
                         */
                        className="group-data-[collapsible=icon]:p-2!"
                    >
                        <Link href={workspaces.create()}>
                            <Plus className="size-4" />
                            <span className="truncate font-medium">
                                {t('Create workspace')}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent group-data-[collapsible=icon]:p-2!"
                            data-test="workspace-switcher"
                        >
                            <LayoutGrid className="size-4 shrink-0" />
                            <span className="truncate font-medium" dir="auto">
                                {current?.name ?? t('Workspaces')}
                            </span>
                            <ChevronsUpDown className="ms-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? direction === 'rtl'
                                      ? 'left'
                                      : 'right'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            {t('Workspaces')}
                        </DropdownMenuLabel>

                        {available.map((workspace) => (
                            <DropdownMenuItem
                                key={workspace.id}
                                onSelect={() =>
                                    router.post(
                                        workspaces.activate.url(workspace.slug),
                                    )
                                }
                                className="gap-2"
                            >
                                <Check
                                    className={
                                        workspace.slug === current?.slug
                                            ? 'size-4 opacity-100'
                                            : 'size-4 opacity-0'
                                    }
                                />
                                <span className="truncate" dir="auto">
                                    {workspace.name}
                                </span>
                            </DropdownMenuItem>
                        ))}

                        <DropdownMenuSeparator />

                        {current && (
                            <DropdownMenuItem
                                onSelect={() =>
                                    router.visit(workspaces.show(current.slug))
                                }
                                className="gap-2"
                                data-test="manage-workspace"
                            >
                                <Settings2 className="size-4" />
                                {t('Manage workspace')}
                            </DropdownMenuItem>
                        )}

                        <DropdownMenuItem
                            onSelect={() => router.visit(workspaces.create())}
                            className="gap-2"
                        >
                            <Plus className="size-4" />
                            {t('Create workspace')}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
