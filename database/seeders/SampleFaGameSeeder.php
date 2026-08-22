<?php

namespace Database\Seeders;

use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;

/**
 * The Persian workshop's projects.
 *
 * Same shelf as the English studio and none of the same games: one flagship in playtesting, one
 * still finding its core, one parked, one abandoned, and two next door.
 *
 * The mechanics are referenced by their English slugs — `worker-placement`, `push-your-luck` —
 * because that is what the design vocabulary is. `MechanicSeeder` stores the canonical English
 * term and `MechanicResource` translates it through `__()` on the way out, so a Persian designer
 * picking «جانمایی کارگر» and an English one picking "Worker placement" are recording the same
 * fact about their game. The vocabulary is shared; only the games written with it are not.
 */
class SampleFaGameSeeder extends SampleGameSeeder
{
    /**
     * The shelf.
     *
     * @return list<array<string, mixed>>
     */
    protected function games(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'slug' => 'karvansara',
                'name' => 'کاروان‌سرا',
                'description' => 'بازی جانمایی کارگر دربارهٔ گرداندن یک کاروان‌سرا سر راه تجاری. '
                    .'پروژهٔ اصلی کارگاه و جلوتر از همه.',
                'status' => GameStatus::Active,
                'phase' => DesignPhase::Playtesting,
                'created_by' => 'negar@simorgh.test',
                'started' => 352,
                'touched' => 1,
                'versions' => [
                    ['name' => 'اولین بازی‌شدنی', 'by' => 'negar@simorgh.test', 'cut' => 338, 'description' => 'سه خدمه، شش بخش و فصلی که هر دور یک گام جلو می‌رود. سفارش‌ها یک ردیف مشترک چهارتایی‌اند.'],
                    ['name' => 'گردونهٔ فصل', 'by' => 'arash@simorgh.test', 'cut' => 176, 'description' => 'فصل روی گردونهٔ خودش می‌چرخد جایی که همه می‌بینندش، و بخش‌ها با آن باز و بسته می‌شوند نه با یادآوری قواعد.'],
                    ['name' => 'بازنویسی مهمان‌داری', 'by' => 'negar@simorgh.test', 'cut' => 52, 'description' => 'کاروان‌ها به جای ردیف مشترک از سه صف جدا می‌آیند، پس پذیرفتن کاروانی که می‌خواهید یعنی نپذیرفتن کاروانی که کس دیگری منتظرش بود.'],
                ],
                'design' => [
                    'pitch' => 'بازی‌ای دربارهٔ گرداندن یک کاروان‌سرا سر راه تجاری، که بازیکن‌ها خدمه را '
                        .'می‌فرستند تا کاروان‌ها را پیش از رفتن فصل جا بدهند و سیر کنند.',
                    'player_count_min' => 2,
                    'player_count_max' => 4,
                    'play_time_min' => 45,
                    'play_time_max' => 75,
                    'target_age_min' => 12,
                    'complexity' => Complexity::Hobby,
                    'audience' => 'کسانی که یک بازی جانمایی کارگر دارند و دنبال بازی‌ای هستند که تنگنایش '
                        .'زمان باشد نه کمبود کارگر.',
                    'core_action' => 'یکی از سه خدمه‌تان را به اتاق‌ها، اصطبل، آشپزخانه یا چاه بفرستید و '
                        .'کنش آن بخش را بردارید.',
                    'core_cost' => 'خدمه‌ای که فرستاده شد تا چرخش فصل برنمی‌گردد، پس هر کنش یک‌سوم توان '
                        .'شما در این فصل را می‌گیرد.',
                    'core_reward' => 'کاروانی که سیر و جا داده شود سکه و آبرو می‌گذارد، و آبرو هم امتیاز '
                        .'است و هم تنها راه باز کردن مسیر تازه.',
                    'win_condition' => 'بیشترین آبرو پس از آنکه گردونهٔ فصل چهار دور کامل بزند.',
                    'failure_condition' => 'مهمانی که تا پایان فصل سیر نشود کاروان‌سرا را رها می‌کند، و '
                        .'آبرویی که با خود می‌برد رو باز کنار گذاشته می‌شود تا همه ببینند چه از دست داده‌اید.',
                    'mechanics' => [
                        'worker-placement',
                        'income-and-upkeep',
                        'contracts',
                        'network-building',
                        'victory-points',
                        'end-game-bonuses',
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'slug' => 'qanat',
                'name' => 'قنات',
                'description' => 'بازی‌ای دربارهٔ کندن کاریز، که تصمیم جالبش این است که کِی به آب بزنید، '
                    .'نه اینکه از کجا بکنید.',
                'status' => GameStatus::Active,
                'phase' => DesignPhase::CoreDesign,
                'created_by' => 'arash@simorgh.test',
                'started' => 96,
                'touched' => 5,
                'versions' => [
                    ['name' => 'شکل اول', 'by' => 'arash@simorgh.test', 'cut' => 92, 'description' => 'یک کاریز مشترک، کاشی‌های حفر، و آبی که هر وقت کسی بگوید جاری می‌شود.'],
                    ['name' => 'کاریز جداگانه', 'by' => 'arash@simorgh.test', 'cut' => 31, 'description' => 'هر بازیکن کاریز خودش را می‌کند و می‌تواند در کاریز دیگری سرک بکشد، که تصمیم زمان‌بندی را از حساب‌وکتاب به خواندن حریف تبدیل می‌کند.'],
                ],
                'design' => [
                    'pitch' => 'بازی‌ای دربارهٔ کندن کاریز، که هرچه عمیق‌تر بروید آب بیشتری هست و ریزش '
                        .'نزدیک‌تر.',
                    'player_count_min' => 2,
                    'player_count_max' => 4,
                    'play_time_min' => 40,
                    'play_time_max' => 60,
                    'target_age_min' => 10,
                    'complexity' => Complexity::Gateway,
                    'audience' => 'جمع‌های خانوادگی که یک بازی کوتاه با یک تصمیم سخت می‌خواهند.',
                    'core_action' => 'یک کاشی حفر بگذارید و کاریز را یک گام جلو ببرید، یا آب را باز کنید.',
                    'core_cost' => 'هر گام جلوتر، کارگر می‌خواهد و خطر ریزش را یک درجه بالا می‌برد.',
                    'core_reward' => 'کاریزی که به آب برسد تا پایان بازی هر دور آب می‌دهد.',
                    'win_condition' => 'بیشترین آب رسیده به مزرعه در پایان دور هشتم.',

                    /*
                     * Deliberately blank. Arash has not settled what happens when a channel
                     * collapses, which is what the core-readiness checklist is about to say.
                     */
                    'failure_condition' => null,
                    'mechanics' => [
                        'tile-placement',
                        'network-building',
                        'push-your-luck',
                        'hidden-information',
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'slug' => 'shab-e-yalda',
                'name' => 'شب یلدا',
                'description' => 'بازی مهمانی برای بلندترین شب سال. تا تمام شدن کاروان‌سرا کنار گذاشته شده.',
                'status' => GameStatus::OnHold,
                'phase' => DesignPhase::Concept,
                'created_by' => 'sahar@simorgh.test',
                'started' => 154,
                'touched' => 66,
                'versions' => [
                    ['name' => 'طرح اولیه', 'by' => 'sahar@simorgh.test', 'cut' => 152, 'description' => 'کارت‌های شعر، سهم انار، و دور زدن حافظ. هنوز ساختار نوبت ندارد.'],
                ],
                'design' => [
                    'pitch' => 'بازی‌ای برای شب یلدا که بازیکن‌ها با فال و شعر و خوراکی امتیاز جمع '
                        .'می‌کنند و کسی زودتر از نیمه‌شب برنده نمی‌شود.',
                    'player_count_min' => 3,
                    'player_count_max' => 8,
                    'play_time_min' => 20,
                    'play_time_max' => 45,
                    'target_age_min' => 8,
                    'complexity' => Complexity::Party,
                    'audience' => 'خانواده‌هایی که شب یلدا دور هم جمع‌اند و بازی‌ای می‌خواهند که بشود '
                        .'وسطش حرف زد و میوه خورد.',
                    'core_action' => null,
                    'core_cost' => null,
                    'core_reward' => null,
                    'win_condition' => null,
                    'failure_condition' => null,
                    'mechanics' => [
                        'set-collection',
                        'simultaneous-action',
                        'hand-management',
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'slug' => 'badgir',
                'name' => 'بادگیر',
                'description' => 'بازی‌ای دربارهٔ خنک کردن یک شهر کویری، رهاشده در مرحلهٔ نمونه. نگه '
                    .'داشته شده چون گردونهٔ فصلِ کاروان‌سرا از دلش درآمد.',
                'status' => GameStatus::Archived,
                'phase' => DesignPhase::Prototyping,
                'created_by' => 'negar@simorgh.test',
                'started' => 372,
                'touched' => 268,
                'versions' => [
                    ['name' => 'اولین بازی‌شدنی', 'by' => 'negar@simorgh.test', 'cut' => 366, 'description' => 'شبکهٔ خانه‌ها، کاشی‌های بادگیر، و نوار گرمایی که چه استفاده کنید چه نکنید بالا می‌رود.'],
                    ['name' => 'بازنویسی باد', 'by' => 'kamran@simorgh.test', 'cut' => 296, 'description' => 'جهت باد هر دور عوض می‌شود، به این امید که جای گذاشتن بادگیر تصمیم بشود.'],
                ],
                'design' => [
                    'pitch' => 'بازی‌ای دربارهٔ خنک نگه داشتن یک شهر کویری، که گرما ساعت بازی است.',
                    'player_count_min' => 2,
                    'player_count_max' => 5,
                    'play_time_min' => 60,
                    'play_time_max' => 90,
                    'target_age_min' => 14,
                    'complexity' => Complexity::Hobby,
                    'audience' => 'کسانی که بازی‌های شبکه‌سازی دوست دارند و از باختن یک محله ناراحت نمی‌شوند.',
                    'core_action' => 'یک بادگیر بگذارید، یا خانه‌ای را به شبکهٔ آب وصل کنید.',
                    'core_cost' => 'هر ساخت، خشت می‌خواهد و خشت فقط در دورهای خنک به دست می‌آید.',
                    'core_reward' => 'محله‌ای که خنک بماند تا پایان بازی امتیاز می‌دهد.',
                    'win_condition' => 'بیشترین محلهٔ خنک‌مانده وقتی نوار گرما پر شود.',
                    'failure_condition' => 'محله‌ای که دو دور پشت‌سرهم گرم بماند خالی می‌شود و از بازی '
                        .'بیرون می‌رود.',
                    'mechanics' => [
                        'tile-placement',
                        'network-building',
                        'area-control',
                        'resource-management',
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'slug' => 'chai-o-khorma',
                'name' => 'چای و خرما',
                'description' => 'بازی کارتی کوچک با یک دور پیشنهاد، نزدیک به تمام‌شدن. کتابچه نوشته '
                    .'شده و تست‌های کور در جریان است.',
                'status' => GameStatus::Active,
                'phase' => DesignPhase::Development,
                'created_by' => 'mahsa@otagh.test',
                'started' => 192,
                'touched' => 6,
                'versions' => [
                    ['name' => 'نسخهٔ آشپزخانه', 'by' => 'mahsa@otagh.test', 'cut' => 188, 'description' => 'چهار خال، یک پیشنهاد پیش از دست، و استکانی که به آخرین برنده می‌رسد.'],
                    ['name' => 'بازنویسی تعارف', 'by' => 'mahsa@otagh.test', 'cut' => 104, 'description' => 'تعارف خرج می‌شود تا پیشنهاد را عوض کند، نه اینکه با بردن دست به دست بیاید — و همین جلوی گریختن نفر جلو را می‌گیرد.'],
                    ['name' => 'نسخهٔ کتابچه', 'by' => 'mahsa@otagh.test', 'cut' => 44, 'description' => 'همان نسخه‌ای که کتابچهٔ نوشته‌شده توصیفش می‌کند. از این‌جا به بعد هیچ چیزی بدون کتابچهٔ تازه عوض نمی‌شود.'],
                ],
                'design' => [
                    'pitch' => 'بازی دست‌گیری دربارهٔ مهمانی‌ای که بازندهٔ پیشنهاد خال بعدی را انتخاب '
                        .'می‌کند، پس اشتباه کردن هم چیزی می‌ارزد.',
                    'player_count_min' => 3,
                    'player_count_max' => 5,
                    'play_time_min' => 25,
                    'play_time_max' => 40,
                    'target_age_min' => 12,
                    'complexity' => Complexity::Family,
                    'audience' => 'کسانی که حکم و شلم بازی کرده‌اند و بازی‌ای می‌خواهند که هر دست فرق '
                        .'کند بی‌آنکه دسته‌کارت تازه بخواهد.',
                    'core_action' => 'پیشنهاد بدهید چند دست می‌برید، بعد یک کارت به دست بیندازید.',
                    'core_cost' => 'پیشنهاد بلند گفته می‌شود و پایین نمی‌آید؛ تعارفی که خرج تغییرش کنید '
                        .'تعارفی است که امتیازش را نمی‌گیرید.',
                    'core_reward' => 'رسیدن دقیق به پیشنهاد امتیاز دارد، و استکان به کسی می‌رسد که '
                        .'بیشترین فاصله را با پیشنهادش داشته.',
                    'win_condition' => 'اولین کسی که در پایان یک دست به چهارده امتیاز برسد.',
                    'failure_condition' => 'داشتن استکان در پایان دست یک امتیاز کم می‌کند، و باید دست '
                        .'کسی بماند.',
                    'mechanics' => [
                        'trick-taking',
                        'auction',
                        'hand-management',
                        'catch-up-mechanism',
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'slug' => 'haft-sang',
                'name' => 'هفت‌سنگ',
                'description' => 'ایده‌ای از هفتهٔ پیش. یک جمله و هیچ چیز دیگر.',
                'status' => GameStatus::Draft,
                'phase' => DesignPhase::Idea,
                'created_by' => 'mahsa@otagh.test',
                'started' => 12,
                'touched' => 12,
                'versions' => [
                    ['name' => null, 'by' => 'mahsa@otagh.test', 'cut' => 12, 'description' => null],
                ],
                'design' => [
                    'pitch' => 'بازی‌ای دربارهٔ چیدن سنگ‌ها روی هم، که هر سنگی که می‌گذارید قولی است '
                        .'دربارهٔ کاری که دور بعد می‌کنید.',
                    'mechanics' => [],
                ],
            ],
        ];
    }
}
