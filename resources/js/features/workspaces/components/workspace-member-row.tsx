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
import { useTranslation } from '@/lib/i18n';
import type { WorkspaceMember, WorkspacePermissions } from '../types/workspace';
import { WORKSPACE_ROLE_LABEL } from '../types/workspace';

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
    const { t } = useTranslation();
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
                    <span dir="auto">
                        {member.user?.name ?? t('Unknown member')}
                    </span>
                    {isSelf && (
                        <span className="ms-1.5 text-muted-foreground">
                            {t('(you)')}
                        </span>
                    )}
                </p>
                <p
                    className="truncate text-sm text-muted-foreground"
                    dir="auto"
                >
                    {member.user?.email}
                </p>
            </div>

            <Badge variant={isOwner ? 'default' : 'secondary'}>
                {isOwner && <ShieldCheck />}
                {t(WORKSPACE_ROLE_LABEL[member.role])}
            </Badge>

            {hasActions && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('Manage :name', {
                                name: member.user?.name ?? t('member'),
                            })}
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
                                {t('Change role')}
                            </DropdownMenuItem>
                        )}

                        {canRemove && (
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => onRemove(member)}
                            >
                                <UserMinus className="size-4" />
                                {t('Remove from workspace')}
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </li>
    );
}
