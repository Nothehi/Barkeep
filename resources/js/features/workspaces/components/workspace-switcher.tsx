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

    if (!hasWorkspaces) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton asChild size="lg">
                        <Link href={workspaces.create()}>
                            <Plus className="size-4" />
                            <span className="truncate font-medium">
                                Create workspace
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
                            className="data-[state=open]:bg-sidebar-accent"
                            data-test="workspace-switcher"
                        >
                            <LayoutGrid className="size-4 shrink-0" />
                            <span className="truncate font-medium">
                                {current?.name ?? 'Workspaces'}
                            </span>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'right'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Workspaces
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
                                <span className="truncate">
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
                                Manage workspace
                            </DropdownMenuItem>
                        )}

                        <DropdownMenuItem
                            onSelect={() => router.visit(workspaces.create())}
                            className="gap-2"
                        >
                            <Plus className="size-4" />
                            Create workspace
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
