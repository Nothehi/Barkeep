<?php

namespace Database\Seeders;

use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * The people and the two studios the rest of the sample data belongs to.
 *
 * Two workspaces rather than one, because almost everything that is interesting
 * about tenancy is invisible with a single tenant: that a game's address is only
 * unique inside its workspace, that a person can hold different roles in
 * different studios, and that the workspace switcher has something to switch
 * between.
 *
 * Mara Okonkwo is deliberately in both — she owns Lantern & Anvil and consults
 * for Nightshift Games — so signing in as her exercises the selection screen
 * rather than skipping straight past it.
 *
 * Every account uses the password `password`. This seeder is sample data and
 * says so; it is not called from `DatabaseSeeder` in production.
 */
class SampleStudioSeeder extends SampleSeeder
{
    /**
     * The studio the bulk of the sample data belongs to.
     */
    public const LANTERN = 'lantern-and-anvil';

    /**
     * The one-person studio next door, and the reason tenancy is visible.
     */
    public const NIGHTSHIFT = 'nightshift-games';

    /**
     * Seed the accounts, the workspaces and their membership.
     */
    public function run(): void
    {
        foreach ($this->people() as $person) {
            $this->account($person);
        }

        foreach ($this->workspaces() as $definition) {
            $workspace = $this->workspaceRecord($definition);

            foreach ($definition['members'] as $member) {
                $this->membership($workspace, $member['email'], $member['role'], $member['joined']);
            }

            foreach ($definition['invitations'] ?? [] as $invitation) {
                $this->invitation($workspace, $invitation);
            }
        }

        $this->command->info(sprintf(
            'Seeded %d accounts across %d sample workspaces.',
            count($this->people()),
            count($this->workspaces()),
        ));
    }

    /**
     * The studio's accounts.
     *
     * `last_login_at` is spread out on purpose: a members list where everybody
     * signed in at the same second is the clearest possible sign that nobody
     * really did.
     *
     * @return list<array{name: string, email: string, status: UserStatus, joined: int, seen: int}>
     */
    protected function people(): array
    {
        return [
            ['name' => 'Mara Okonkwo', 'email' => 'mara@lanternandanvil.test', 'status' => UserStatus::Active, 'joined' => 430, 'seen' => 0],
            ['name' => 'Devin Halloran', 'email' => 'devin@lanternandanvil.test', 'status' => UserStatus::Active, 'joined' => 425, 'seen' => 1],
            ['name' => 'Priya Raman', 'email' => 'priya@lanternandanvil.test', 'status' => UserStatus::Active, 'joined' => 268, 'seen' => 2],
            ['name' => 'Tomas Lindqvist', 'email' => 'tomas@lanternandanvil.test', 'status' => UserStatus::Active, 'joined' => 96, 'seen' => 11],
            ['name' => 'Yusuf Demir', 'email' => 'yusuf@nightshiftgames.test', 'status' => UserStatus::Active, 'joined' => 280, 'seen' => 3],

            /*
             * A former contributor. Suspended rather than deleted, because
             * every game and observation she wrote still points at this row —
             * which is the reason `restrictOnDelete` guards the creator columns
             * in the first place.
             */
            ['name' => 'Ilse Vermeer', 'email' => 'ilse@lanternandanvil.test', 'status' => UserStatus::Suspended, 'joined' => 390, 'seen' => 214],
        ];
    }

    /**
     * Write one account, keyed by its address.
     *
     * @param  array{name: string, email: string, status: UserStatus, joined: int, seen: int}  $person
     */
    private function account(array $person): User
    {
        $user = User::query()->firstOrNew(['email' => $person['email']]);

        $user->fill([
            'name' => $person['name'],
            'email' => $person['email'],
        ]);

        /*
         * Only set on first write. Re-seeding should not silently reset the
         * password of an account somebody is in the middle of using.
         */
        if (! $user->exists) {
            $user->password = 'password';
        }

        $user->status = $person['status'];
        $user->email_verified_at = $this->daysAgo($person['joined'], 9);
        $user->last_login_at = $this->daysAgo($person['seen'], 8);

        $this->stamp($user, $this->daysAgo($person['joined'], 9), $this->daysAgo($person['seen'], 8));
        $user->save();

        return $user;
    }

    /**
     * Write one workspace, keyed by its address.
     *
     * @param  array<string, mixed>  $definition
     */
    private function workspaceRecord(array $definition): Workspace
    {
        $workspace = Workspace::query()->firstOrNew(['slug' => $definition['slug']]);

        $workspace->fill([
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $workspace->slug = $definition['slug'];
        $workspace->owner_id = $this->user($definition['owner'])->id;
        $workspace->status = WorkspaceStatus::Active;

        $this->stamp($workspace, $this->daysAgo($definition['founded'], 9));
        $workspace->save();

        return $workspace;
    }

    /**
     * Give somebody a role in a workspace.
     *
     * Keyed by the pair the unique index is on, so re-seeding corrects a role
     * rather than colliding with it.
     */
    private function membership(Workspace $workspace, string $email, WorkspaceRole $role, int $joinedDaysAgo): void
    {
        $user = $this->user($email);

        $member = WorkspaceMember::query()->firstOrNew([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->id,
        ]);

        $member->workspace_id = $workspace->getKey();
        $member->user_id = $user->id;
        $member->role = $role;
        $member->joined_at = $this->daysAgo($joinedDaysAgo, 9);

        $this->stamp($member, $this->daysAgo($joinedDaysAgo, 9));
        $member->save();
    }

    /**
     * Write one invitation.
     *
     * The token digest is derived from the workspace and the address rather
     * than generated, so re-seeding produces the same row instead of tripping
     * the unique index on a fresh random value. No real token exists for it,
     * which is correct: a seeded invitation is a record of one being sent, not
     * a working way in.
     *
     * @param  array{email: string, role: WorkspaceRole, status: InvitationStatus, by: string, sent: int}  $definition
     */
    private function invitation(Workspace $workspace, array $definition): void
    {
        $status = $definition['status'];

        $digest = hash('sha256', $workspace->slug.'|'.$definition['email'].'|'.$status->value);

        $invitation = WorkspaceInvitation::query()->firstOrNew(['token_hash' => $digest]);

        $invitation->workspace_id = $workspace->getKey();
        $invitation->email = $definition['email'];
        $invitation->role = $definition['role'];
        $invitation->token_hash = $digest;
        $invitation->status = $status;
        $invitation->created_by = $this->user($definition['by'])->id;

        $sent = $this->daysAgo($definition['sent'], 11);

        /*
         * A pending invitation has to still be open when somebody looks at it,
         * and every other state is a matter of record, so its window is
         * measured from the day it was sent.
         */
        $invitation->expires_at = $status === InvitationStatus::Pending
            ? $this->daysAhead(10)
            : $sent->addDays(14);

        $invitation->accepted_at = $status === InvitationStatus::Accepted ? $sent->addDays(1) : null;
        $invitation->revoked_at = $status === InvitationStatus::Revoked ? $sent->addDays(3) : null;

        $this->stamp($invitation, $sent, $invitation->accepted_at ?? $invitation->revoked_at ?? $sent);
        $invitation->save();
    }

    /**
     * The workspaces, who is in them, and who has been asked.
     *
     * The invitation lists carry one of each state on purpose, because the
     * states are what the screen is for: a pending invitation can be revoked or
     * resent, an expired one explains why somebody never arrived, and an
     * accepted one is the audit trail for a member already in the list.
     *
     * @return list<array<string, mixed>>
     */
    protected function workspaces(): array
    {
        return [
            [
                'slug' => self::LANTERN,
                'name' => 'Lantern & Anvil',
                'description' => 'A four-person studio making mid-weight games about work: ports, kilns, '
                    .'and the people who keep them running.',
                'owner' => 'mara@lanternandanvil.test',
                'founded' => 430,
                'members' => [
                    ['email' => 'mara@lanternandanvil.test', 'role' => WorkspaceRole::Owner, 'joined' => 430],
                    ['email' => 'devin@lanternandanvil.test', 'role' => WorkspaceRole::Admin, 'joined' => 425],
                    ['email' => 'priya@lanternandanvil.test', 'role' => WorkspaceRole::Member, 'joined' => 268],
                    ['email' => 'tomas@lanternandanvil.test', 'role' => WorkspaceRole::Member, 'joined' => 96],
                ],
                'invitations' => [
                    ['email' => 'hannah.wu@freelanceplaytest.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Pending, 'by' => 'mara@lanternandanvil.test', 'sent' => 4],
                    ['email' => 'tomas@lanternandanvil.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Accepted, 'by' => 'mara@lanternandanvil.test', 'sent' => 99],
                    ['email' => 'oscar.reyes@freelanceplaytest.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Revoked, 'by' => 'devin@lanternandanvil.test', 'sent' => 61],
                    ['email' => 'lena.fischer@freelanceplaytest.test', 'role' => WorkspaceRole::Admin, 'status' => InvitationStatus::Expired, 'by' => 'mara@lanternandanvil.test', 'sent' => 150],
                ],
            ],
            [
                'slug' => self::NIGHTSHIFT,
                'name' => 'Nightshift Games',
                'description' => 'Yusuf Demir on his own: card games drafted between shifts and tested in the staff room.',
                'owner' => 'yusuf@nightshiftgames.test',
                'founded' => 280,
                'members' => [
                    ['email' => 'yusuf@nightshiftgames.test', 'role' => WorkspaceRole::Owner, 'joined' => 280],
                    ['email' => 'mara@lanternandanvil.test', 'role' => WorkspaceRole::Member, 'joined' => 84],
                ],
                'invitations' => [
                    ['email' => 'mara@lanternandanvil.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Accepted, 'by' => 'yusuf@nightshiftgames.test', 'sent' => 86],
                ],
            ],
        ];
    }
}
