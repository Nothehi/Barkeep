<?php

namespace Database\Seeders;

use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Enums\WorkspaceRole;

/**
 * A Persian studio, its people and the two workspaces they work in.
 *
 * The reason this exists at all: a game's name, its pitch, an observation somebody wrote at a
 * playtest and a note against a balance assumption are all authored content. They are stored as
 * written and never go through `__()`, so a Persian reader looking at the English sample data sees
 * an English studio with a Persian interface around it — which demonstrates the localisation and
 * hides what the screens are actually for.
 *
 * Addresses stay Latin. The workspace slug is a URL segment and so is a game's, and a Persian slug
 * would reach the address bar percent-encoded; `Str::slug('کارگاه سیمرغ')` is worse still. The name
 * is what people read, the slug is what the URL uses, and the two are allowed to differ.
 *
 * Every account uses the password `password`, like the English studio.
 */
class SampleFaStudioSeeder extends SampleStudioSeeder
{
    /**
     * The workshop most of the Persian sample data belongs to.
     */
    public const SIMORGH = 'kargah-simorgh';

    /**
     * The room down the corridor, and the reason tenancy is visible in Persian too.
     */
    public const OTAGH = 'otagh-poshti';

    /**
     * The workshop's accounts.
     *
     * @return list<array{name: string, email: string, status: UserStatus, joined: int, seen: int}>
     */
    protected function people(): array
    {
        return [
            ['name' => 'نگار موسوی', 'email' => 'negar@simorgh.test', 'status' => UserStatus::Active, 'joined' => 384, 'seen' => 0],
            ['name' => 'آرش کیانی', 'email' => 'arash@simorgh.test', 'status' => UserStatus::Active, 'joined' => 376, 'seen' => 1],
            ['name' => 'سحر جوادی', 'email' => 'sahar@simorgh.test', 'status' => UserStatus::Active, 'joined' => 214, 'seen' => 3],
            ['name' => 'بهرام رستمی', 'email' => 'bahram@simorgh.test', 'status' => UserStatus::Active, 'joined' => 88, 'seen' => 8],
            ['name' => 'مهسا فراهانی', 'email' => 'mahsa@otagh.test', 'status' => UserStatus::Active, 'joined' => 196, 'seen' => 2],

            /*
             * Left the workshop and still named on two years of observations. Suspended rather than
             * deleted, for the same reason as the English studio's former contributor.
             */
            ['name' => 'کامران دهقان', 'email' => 'kamran@simorgh.test', 'status' => UserStatus::Suspended, 'joined' => 360, 'seen' => 188],
        ];
    }

    /**
     * The two workspaces, who is in them, and who has been asked.
     *
     * @return list<array<string, mixed>>
     */
    protected function workspaces(): array
    {
        return [
            [
                'slug' => self::SIMORGH,
                'name' => 'کارگاه سیمرغ',
                'description' => 'کارگاهی چهارنفره که بازی‌های میان‌وزن می‌سازد دربارهٔ کار کردن: '
                    .'کاروان‌سرا، قنات، و آدم‌هایی که این‌ها را می‌گردانند.',
                'owner' => 'negar@simorgh.test',
                'founded' => 384,
                'members' => [
                    ['email' => 'negar@simorgh.test', 'role' => WorkspaceRole::Owner, 'joined' => 384],
                    ['email' => 'arash@simorgh.test', 'role' => WorkspaceRole::Admin, 'joined' => 376],
                    ['email' => 'sahar@simorgh.test', 'role' => WorkspaceRole::Member, 'joined' => 214],
                    ['email' => 'bahram@simorgh.test', 'role' => WorkspaceRole::Member, 'joined' => 88],
                ],
                'invitations' => [
                    ['email' => 'rezaee@playtest.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Pending, 'by' => 'negar@simorgh.test', 'sent' => 3],
                    ['email' => 'bahram@simorgh.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Accepted, 'by' => 'negar@simorgh.test', 'sent' => 91],
                    ['email' => 'shirazi@playtest.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Revoked, 'by' => 'arash@simorgh.test', 'sent' => 54],
                    ['email' => 'ghaffari@playtest.test', 'role' => WorkspaceRole::Admin, 'status' => InvitationStatus::Expired, 'by' => 'negar@simorgh.test', 'sent' => 142],
                ],
            ],
            [
                'slug' => self::OTAGH,
                'name' => 'اتاق پشتی',
                'description' => 'مهسا فراهانی، تنها. بازی‌های کارتی کوچک که سر میز آشپزخانه طراحی '
                    .'و در کتابخانهٔ محله تست می‌شوند.',
                'owner' => 'mahsa@otagh.test',
                'founded' => 196,
                'members' => [
                    ['email' => 'mahsa@otagh.test', 'role' => WorkspaceRole::Owner, 'joined' => 196],
                    ['email' => 'negar@simorgh.test', 'role' => WorkspaceRole::Member, 'joined' => 73],
                ],
                'invitations' => [
                    ['email' => 'negar@simorgh.test', 'role' => WorkspaceRole::Member, 'status' => InvitationStatus::Accepted, 'by' => 'mahsa@otagh.test', 'sent' => 75],
                ],
            ],
        ];
    }
}
