import { MoreHorizontal, ShieldCheck, UserMinus } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import type { WorkspaceMember, WorkspacePermissions } from '../types/workspace';

type WorkspaceMemberRowProps = {
    member: WorkspaceMember;
    permissions: WorkspacePermissions;
    isSelf: boolean;
    onChangeRole: (member: WorkspaceMember) => void;
    onRemove: (member: WorkspaceMember) => void;
};

/**
 * One person in the member list, with the actions available on them.
 *
 * The owner is shown without actions: their role can only move through an
 * ownership transfer, and they cannot be removed at all. That is enforced by
 * the policy and again by the application layer; the row simply stops
 * offering something that would always be refused.
 */
export default function WorkspaceMemberRow({
    member,
    permissions,
    isSelf,
    onChangeRole,
    onRemove,
}: WorkspaceMemberRowProps) {
    const getInitials = useInitials();
    const isOwner = member.role === 'owner';

    const canChangeRole = permissions.canChangeRoles && !isOwner;
    const canRemove = permissions.canRemoveMembers && !isOwner && !isSelf;
    const hasActions = canChangeRole || canRemove;

    return (
        <li className="flex items-center gap-3 px-4 py-3">
            <Avatar className="size-9">
                <AvatarFallback>
                    {getInitials(member.user?.name ?? '?')}
                </AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">
                    {member.user?.name ?? 'Unknown member'}
                    {isSelf && (
                        <span className="ml-1.5 text-muted-foreground">
                            (you)
                        </span>
                    )}
                </p>
                <p className="truncate text-sm text-muted-foreground">
                    {member.user?.email}
                </p>
            </div>

            <Badge variant={isOwner ? 'default' : 'secondary'}>
                {isOwner && <ShieldCheck />}
                {member.role}
            </Badge>

            {hasActions && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={`Manage ${member.user?.name ?? 'member'}`}
                        >
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end">
                        {canChangeRole && (
                            <DropdownMenuItem
                                onSelect={() => onChangeRole(member)}
                            >
                                <ShieldCheck className="size-4" />
                                Change role
                            </DropdownMenuItem>
                        )}

                        {canRemove && (
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => onRemove(member)}
                            >
                                <UserMinus className="size-4" />
                                Remove from workspace
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </li>
    );
}
