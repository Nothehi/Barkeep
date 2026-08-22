<?php

namespace Database\Seeders;

use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;

/**
 * The sessions the Persian workshop's designs were put in front of people at.
 *
 * Same discipline as the English studio's: every playtest has a question in its objective and, once
 * it is finished, an answer in its conclusion. Guests outnumber accounts, because a workshop's
 * playtesters are mostly not users of the workshop's tools.
 */
class SampleFaPlaytestSeeder extends SamplePlaytestSeeder
{
    /**
     * The sessions themselves.
     *
     * @return list<array<string, mixed>>
     */
    protected function playtests(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 2,
                'title' => 'آیا گردونهٔ فصل با یک نگاه خوانده می‌شود؟',
                'objective' => 'ببینیم بازیکن‌ها می‌توانند بدون پرسیدن بگویند کدام بخش زودتر بسته '
                    .'می‌شود، حالا که فصل گردونهٔ خودش را دارد به جای یک خط روی برگهٔ قواعد.',
                'hypothesis' => 'گذاشتن فصل جایی که همه می‌بینند، پرسیدن دربارهٔ بسته شدن بخش‌ها را تمام می‌کند.',
                'conclusion' => 'از دور دوم پرسیدن تمام شد. بازیکن‌ها گردونه را هم تهدید خواندند نه '
                    .'برنامه — پیش از انتخاب رویش خم می‌شدند — که بیش از چیزی است که تستش می‌کردیم و '
                    .'حالا همان چیزی است که بازی دربارهٔ آن است.',
                'status' => PlaytestStatus::Completed,
                'by' => 'negar@simorgh.test',
                'planned' => 168,
                'completed' => 152,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'کارگاه، جمع سه‌شنبه',
                        'by' => 'negar@simorgh.test',
                        'held' => 168,
                        'ran' => 96,
                        'notes' => 'اولین اجرای گردونهٔ فصل. بخش‌ها هنوز کارت فیش بودند و گردونه نواری '
                            .'چاپی که با یک لیوان نگهش داشته بودیم.',
                        'outcome' => 'بعد از دور اول کسی نپرسید بخشی کِی بسته می‌شود. دو بازیکن جهت '
                            .'چرخش را برعکس خواندند، که مشکل چیدمان است نه قواعد.',
                        'participants' => [
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Designer, 'account' => 'negar@simorgh.test'],
                            ['name' => 'آرش کیانی', 'role' => PlaytestParticipantRole::Player, 'account' => 'arash@simorgh.test'],
                            ['name' => 'مریم رضایی', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'حسین شیرازی', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Ux, 'content' => 'هر دو بازیکن تازه گردونه را ساعتگرد خواندند. پادساعتگرد می‌چرخد. خودشان تا آخر دور اصلاح کردند ولی فلش باید روی گردونه باشد نه در قواعد.', 'by' => 'negar@simorgh.test', 'about' => 'مریم رضایی'],
                            ['category' => ObservationCategory::PlayerBehavior, 'content' => 'حسین از دور سوم به بعد پیش از هر جانمایی روی میز خم می‌شد تا گردونه را ببیند.', 'by' => 'negar@simorgh.test', 'about' => 'حسین شیرازی'],
                            ['category' => ObservationCategory::Rules, 'content' => 'پرسید مهمانی که نصفه سیر شده وقتی فصل می‌رود چه می‌شود. تصمیمش را نگرفته بودیم. همان‌جا حکم دادیم که آبرویش را نگه می‌دارد و می‌رود.', 'by' => 'negar@simorgh.test', 'about' => 'مریم رضایی'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'فصل چهارم بیست و شش دقیقه از بازی نود و شش دقیقه‌ای را گرفت و دو تصمیم امتیازی تولید کرد.', 'by' => 'negar@simorgh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'خوشم آمد که می‌شد کل شب را از پیش دید. آخرش کش آمد — تا آن‌جا می‌دانستم چه می‌کنم و فقط داشتم انجامش می‌دادم.', 'rating' => 4, 'by' => 'negar@simorgh.test', 'from' => 'مریم رضایی'],
                            ['content' => 'از دست دادن یک کاروان به شکل درستی بد بود. دوباره بازی می‌کنم که این بار نشود.', 'rating' => 5, 'by' => 'negar@simorgh.test', 'from' => 'حسین شیرازی'],
                            ['content' => 'گردونه کار می‌کند. یک فلش رویش بگذار.', 'rating' => 4, 'by' => 'arash@simorgh.test', 'from' => 'آرش کیانی'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'کتابخانهٔ محله، اتاق پشتی',
                        'by' => 'sahar@simorgh.test',
                        'held' => 152,
                        'ran' => 89,
                        'notes' => 'اجرای دوم با فلش چاپ‌شده روی گردونه و بدون حضور کسی از کارگاه سر میز.',
                        'outcome' => 'هیچ‌کس جهت را اشتباه نخواند. مشکل باقی‌مانده طول بازی است: هشتاد '
                            .'و نه دقیقه در چهار نفره برابر سقف اعلام‌شدهٔ هفتاد و پنج.',
                        'participants' => [
                            ['name' => 'سحر جوادی', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'sahar@simorgh.test'],
                            ['name' => 'پیمان اکبری', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'لیلا قنبری', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'یاسر مقدم', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'رؤیا کاشانی', 'role' => PlaytestParticipantRole::Observer],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Ux, 'content' => 'هیچ‌کس جهت گردونه را اشتباه نخواند. فلش کافی بود.', 'by' => 'sahar@simorgh.test'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'هشتاد و نه دقیقه بدون حساب کردن وقت توضیح قواعد. دو فصل اول سی و یک دقیقه و دو فصل آخر پنجاه و هشت دقیقه.', 'by' => 'sahar@simorgh.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'لیلا اصلاً سراغ بازار خشکبار نرفت و دوم شد. یاسر چهار بار استفاده کرد و آخر شد.', 'by' => 'sahar@simorgh.test', 'about' => 'لیلا قنبری'],
                            ['category' => ObservationCategory::PlayerBehavior, 'content' => 'دو دقیقه و نیم فاصله بین نوبت‌های پیمان در فصل چهارم. دو بار گوشی‌اش را نگاه کرد.', 'by' => 'sahar@simorgh.test', 'about' => 'پیمان اکبری'],
                        ],
                        'feedback' => [
                            ['content' => 'بازی خوبی است، بیست دقیقه طولانی‌تر از آنچه باید. اگر بعد از فصل سوم تمام می‌شد راضی‌تر بودم.', 'rating' => 3, 'by' => 'sahar@simorgh.test', 'from' => 'پیمان اکبری'],
                            ['content' => 'تا آخر نفهمیدم بازار خشکبار برای چیست و انگار فرقی هم نکرد.', 'rating' => 4, 'by' => 'sahar@simorgh.test', 'from' => 'لیلا قنبری'],
                            ['content' => 'از بیرون که نگاه می‌کنی، فصل چهارم جایی است که میز ساکت می‌شود. سکوتِ فکر کردن هم نیست.', 'rating' => 3, 'by' => 'sahar@simorgh.test', 'from' => 'رؤیا کاشانی'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'title' => 'آیا سه صف کاروان تصمیم بازداشتن را واقعی می‌کند؟',
                'objective' => 'ببینیم پذیرفتن یک کاروان به عنوان نپذیرفتنش برای دیگری حس می‌شود یا نه، '
                    .'حالا که ردیف مشترک جایش را به سه صف داده.',
                'hypothesis' => 'بازیکن‌ها شروع می‌کنند به تماشای اینکه همسایه‌شان دنبال کدام صف است و '
                    .'عمداً از همان برمی‌دارند.',
                'conclusion' => 'در هر دو جلسه این اتفاق افتاد و کسی راهنمایی‌شان نکرد. بهایش این بود که '
                    .'فصل چهارم بازهم کندتر شد، چون کاروانی که می‌خواهید ممکن است دو تا آن‌طرف‌تر باشد.',
                'status' => PlaytestStatus::Completed,
                'by' => 'negar@simorgh.test',
                'planned' => 43,
                'completed' => 34,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'کارگاه، جمع سه‌شنبه',
                        'by' => 'arash@simorgh.test',
                        'held' => 43,
                        'ran' => 93,
                        'notes' => 'اولین جلسه روی نسخهٔ سوم. سه صف، رو باز، از بالای دسته پر می‌شوند.',
                        'outcome' => 'بازداشتن چهار بار در یک بازی رخ داد و دو بارش برنده را تعیین کرد. '
                            .'طول بازی بدون تغییر و هنوز بیشتر از حد.',
                        'participants' => [
                            ['name' => 'آرش کیانی', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'arash@simorgh.test'],
                            ['name' => 'بهرام رستمی', 'role' => PlaytestParticipantRole::Player, 'account' => 'bahram@simorgh.test'],
                            ['name' => 'مریم رضایی', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'یاسر مقدم', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'مریم کاروانی را پذیرفت که هیچ اتاقی برایش نداشت، فقط برای اینکه به یاسر نرسد. همان موقع بلند گفت که دارد این کار را می‌کند.', 'by' => 'arash@simorgh.test', 'about' => 'مریم رضایی'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'طول نوبت در فصل چهارم بالا رفت، نه پایین. بازیکن‌ها حالا سه صف می‌خوانند به جای یک ردیف.', 'by' => 'arash@simorgh.test'],
                            ['category' => ObservationCategory::Components, 'content' => 'سه صف به علاوهٔ گردونه دیگر کنار چهار منطقهٔ بازیکن روی یک میز معمولی جا نمی‌شود.', 'by' => 'bahram@simorgh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'صف‌ها خیلی بهتر از ردیف‌اند. واقعاً برایم مهم شد که بغل‌دستی‌ام چه جمع می‌کند.', 'rating' => 5, 'by' => 'arash@simorgh.test', 'from' => 'مریم رضایی'],
                            ['content' => 'هنوز بازی نود دقیقه‌ای است که می‌گوید هفتاد و پنج دقیقه است.', 'rating' => 3, 'by' => 'arash@simorgh.test', 'from' => 'یاسر مقدم'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'آنلاین، میز مجازی',
                        'by' => 'negar@simorgh.test',
                        'held' => 34,
                        'ran' => 79,
                        'notes' => 'بازی دو نفره روی نسخهٔ دیجیتال، تا صف‌ها را از مسئلهٔ جای میز جدا کنیم.',
                        'outcome' => 'بازداشتن در دو نفره هم به همان روشنی خوانده می‌شود. هفتاد و نه '
                            .'دقیقه، که داخل بازه است ولی برای کوتاه‌ترین تعداد بازیکن بالای آن.',
                        'participants' => [
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Designer, 'account' => 'negar@simorgh.test'],
                            ['name' => 'پیمان اکبری', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'در دو نفره هر صف هر دور محل نزاع است، که بازداشتن را از گاه‌به‌گاه به دائمی تبدیل می‌کند. شاید بیش از اندازه.', 'by' => 'negar@simorgh.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'پاداش فصل چهارم فاصلهٔ نه امتیازی را به سه رساند. جبران عقب‌ماندگی کارش را می‌کند و شاید بیش از حد.', 'by' => 'negar@simorgh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'دو نفره فشرده‌تر از چیزی است که انتظار داشتم. یک نوبت هم نداشتم که انتخاب واضح باشد.', 'rating' => 5, 'by' => 'negar@simorgh.test', 'from' => 'پیمان اکبری'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'title' => 'آیا بازی چهار نفره زیر هفتاد و پنج دقیقه تمام می‌شود؟',
                'objective' => 'بازی‌های چهار نفره را در برابر سقف اعلام‌شده زمان بگیریم، با فصل چهارمی '
                    .'که از پنج دور به سه دور کوتاه شده.',
                'hypothesis' => 'کوتاه کردن فصل چهارم بازی را بدون دست زدن به پاداش‌های پایانی زیر هفتاد '
                    .'و پنج دقیقه می‌آورد.',
                'status' => PlaytestStatus::InProgress,
                'by' => 'sahar@simorgh.test',
                'planned' => 8,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'کتابخانهٔ محله، اتاق پشتی',
                        'by' => 'sahar@simorgh.test',
                        'held' => 8,
                        'ran' => 82,
                        'notes' => 'فصل چهارم کوتاه‌شده، چهار بازیکن، بدون حضور کسی از کارگاه سر میز.',
                        'outcome' => 'هشتاد و دو دقیقه. هفت دقیقه بهتر از دفعهٔ قبل و هنوز بیشتر. وقت '
                            .'صرفه‌جویی‌شده از بخشی درآمد که مردم دوستش داشتند.',
                        'participants' => [
                            ['name' => 'سحر جوادی', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'sahar@simorgh.test'],
                            ['name' => 'لیلا قنبری', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'یاسر مقدم', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'رؤیا کاشانی', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'حسین شیرازی', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Pacing, 'content' => 'هشتاد و دو دقیقه. سه فصل اول بدون تغییر پنجاه و پنج دقیقه؛ فصل چهارم کوتاه‌شده هنوز بیست و هفت دقیقه گرفت.', 'by' => 'sahar@simorgh.test'],
                            ['category' => ObservationCategory::Gameplay, 'content' => 'دو بازیکن به دور آخر رسیدند بدون اینکه بتوانند کاروانی را که تمام فصل برایش ساخته بودند تمام کنند. هر دو گفتند حس بریده شدن داشت نه باختن.', 'by' => 'sahar@simorgh.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'پاداش‌های پایانی در فصل کوتاه‌تر نسبتاً بیشتر ارزش داشتند، چون دورهای کمتری برای جمع کردن سکه بود.', 'by' => 'sahar@simorgh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'یک دور زودتر از آنچه باید تمام شد. همه چیز را چیده بودم و بعد تمام شد.', 'rating' => 3, 'by' => 'sahar@simorgh.test', 'from' => 'رؤیا کاشانی'],
                            ['content' => 'سریع‌تر شد، ولی نسخهٔ بلند را بیشتر دوست داشتم و من ماه‌هاست از طولش شکایت می‌کنم.', 'rating' => 4, 'by' => 'sahar@simorgh.test', 'from' => 'یاسر مقدم'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Planned,
                        'location' => 'کارگاه، جمع سه‌شنبه',
                        'by' => 'sahar@simorgh.test',
                        'held' => -5,
                        'notes' => 'تکرار در چهار نفره با فصل چهارم برگشته به پنج دور و فصل سوم کوتاه‌شده '
                            .'به سه، تا بفهمیم مردم از طولش ناراحت‌اند یا از پایانش.',
                        'participants' => [
                            ['name' => 'سحر جوادی', 'role' => PlaytestParticipantRole::Facilitator, 'account' => 'sahar@simorgh.test'],
                            ['name' => 'آرش کیانی', 'role' => PlaytestParticipantRole::Player, 'account' => 'arash@simorgh.test'],
                            ['name' => 'مریم رضایی', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'پیمان اکبری', 'role' => PlaytestParticipantRole::Player],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'title' => 'آیا صندلی پنجم با نقش کاروان‌سالار جواب می‌دهد؟',
                'objective' => 'ببینیم می‌شود بازیکن پنجم را به عنوان کاروان‌سالاری اضافه کرد که به جای '
                    .'جانمایی خدمه، ترتیب نوبت را تعیین می‌کند.',
                'hypothesis' => 'بازیکنی بدون خدمه ولی با کنترل ترتیب، کار کافی برای انجام دادن دارد.',
                'status' => PlaytestStatus::Planned,
                'by' => 'negar@simorgh.test',
                'planned' => -11,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Planned,
                        'location' => 'کارگاه، جمع سه‌شنبه',
                        'by' => 'negar@simorgh.test',
                        'held' => -11,
                        'notes' => 'تا وقتی منطقهٔ بازیکن پنجم و کارت مرجع کاروان‌سالار چاپ نشود اجرا '
                            .'نمی‌شود. بهرام دارد هر دو را آماده می‌کند.',
                        'participants' => [
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Designer, 'account' => 'negar@simorgh.test'],
                            ['name' => 'بهرام رستمی', 'role' => PlaytestParticipantRole::Player, 'account' => 'bahram@simorgh.test'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 1,
                'title' => 'آیا سه خدمه با هم فرق دارند؟',
                'objective' => 'ببینیم دادن توانایی ویژه به هر خدمه، تصمیم جانمایی را غنی‌تر می‌کند یا '
                    .'فقط طولانی‌تر.',
                'hypothesis' => 'خدمهٔ متمایز باعث می‌شوند ترتیب خرج کردنشان هم به اندازهٔ جایشان مهم باشد.',
                'conclusion' => 'پیش از اجرا لغو شد. کار روی گردونهٔ فصل پرسش را بی‌موضوع کرد: خدمه با '
                    .'اینکه کِی آزاد می‌شوند از هم متمایز شدند، نه با کاری که می‌کنند.',
                'status' => PlaytestStatus::Cancelled,
                'by' => 'negar@simorgh.test',
                'planned' => 288,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Cancelled,
                        'location' => 'کارگاه، جمع سه‌شنبه',
                        'by' => 'negar@simorgh.test',
                        'held' => 288,
                        'notes' => 'دو روز قبل لغو شد. توانایی‌های ویژه در همان فاصله از طراحی حذف شدند، '
                            .'پس چیزی برای تست کردن نمانده بود.',
                        'participants' => [
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Designer, 'account' => 'negar@simorgh.test'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'qanat',
                'version' => 2,
                'title' => 'آیا وقتی هر دو کاریز دیده می‌شود تصمیمِ آب هنوز جالب است؟',
                'objective' => 'ببینیم دیدن کاریز حریف تصمیم زمان‌بندی را به خواندن او تبدیل می‌کند یا '
                    .'به حساب‌وکتاب.',
                'hypothesis' => 'دیدن کاریز حریف اطلاعات جالب‌تری است از دانستن اینکه چه کاشی‌هایی مانده.',
                'status' => PlaytestStatus::Planned,
                'by' => 'arash@simorgh.test',
                'planned' => -4,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Planned,
                        'location' => 'کارگاه، وقت ناهار',
                        'by' => 'arash@simorgh.test',
                        'held' => -4,
                        'notes' => 'دو نفره، چهار بازی پشت سر هم، با جابه‌جا کردن نفر شروع‌کننده.',
                        'participants' => [
                            ['name' => 'آرش کیانی', 'role' => PlaytestParticipantRole::Designer, 'account' => 'arash@simorgh.test'],
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Player, 'account' => 'negar@simorgh.test'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'badgir',
                'version' => 2,
                'title' => 'آیا جهت باد جای گذاشتن بادگیر را تصمیم می‌کند؟',
                'objective' => 'ببینیم عوض شدن جهت باد در هر دور، انتخاب محل بادگیر را از عادت به تصمیم '
                    .'تبدیل می‌کند یا نه.',
                'hypothesis' => 'بادی که جهتش عوض می‌شود، بادگیر ساخته‌شده در جای اشتباه را پرهزینه '
                    .'می‌کند و انتخاب را واقعی.',
                'conclusion' => 'نشد. بازیکن‌ها یاد گرفتند بادگیر را وسط بگذارند که در هر جهتی نسبتاً خوب '
                    .'است، و بعد از آن جهت باد فقط یک انیمیشن بود. سه تکرار پشت سر هم در همین نقطه شکست '
                    .'خورد و بادگیر بعد از این بایگانی شد.',
                'status' => PlaytestStatus::Completed,
                'by' => 'negar@simorgh.test',
                'planned' => 288,
                'completed' => 274,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'کارگاه، جمع سه‌شنبه',
                        'by' => 'kamran@simorgh.test',
                        'held' => 288,
                        'ran' => 107,
                        'notes' => 'پنج بازیکن، جهت باد متغیر برای اولین بار.',
                        'outcome' => 'سه بازیکن از پنج نفر بادگیرهایشان را وسط گذاشتند و دیگر به جهت باد '
                            .'نگاه نکردند. دو نفر دیگر امتحان کردند و باختند.',
                        'participants' => [
                            ['name' => 'کامران دهقان', 'role' => PlaytestParticipantRole::Designer, 'account' => 'kamran@simorgh.test'],
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Player, 'account' => 'negar@simorgh.test'],
                            ['name' => 'حسین شیرازی', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'یاسر مقدم', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'لیلا قنبری', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'هیچ‌کس جهت باد را تصمیم توصیف نکرد. سه بازیکن صریحاً گفتند گذاشتن بادگیر در وسط بدیهی است.', 'by' => 'kamran@simorgh.test'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'حسین محله‌اش را در دقیقهٔ بیست و چهار از دست داد و هشتاد و سه دقیقهٔ باقی‌مانده کاری نداشت.', 'by' => 'kamran@simorgh.test', 'about' => 'حسین شیرازی'],
                            ['category' => ObservationCategory::PlayerBehavior, 'content' => 'پنج بازیکن، پنج بازی جدا. تمام شب هیچ‌کس به تخته‌ی کسی دیگر نگاه نکرد.', 'by' => 'negar@simorgh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'ده دقیقهٔ اول واقعاً پرتنش است. بعد تمام می‌شود و دو دور دیگر مانده.', 'rating' => 2, 'by' => 'kamran@simorgh.test', 'from' => 'یاسر مقدم'],
                            ['content' => 'بعد از بیست دقیقه بیرون بودم. ترجیح می‌دادم درست‌وحسابی حذف شوم تا اینکه بنشینم.', 'rating' => 1, 'by' => 'kamran@simorgh.test', 'from' => 'حسین شیرازی'],
                        ],
                    ],
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'کارگاه، سه نفره',
                        'by' => 'negar@simorgh.test',
                        'held' => 274,
                        'ran' => 74,
                        'notes' => 'تکرار در سه نفره تا ببینیم وقت مرده تمام مسئله بوده یا نه.',
                        'outcome' => 'انتظار کمتر شد و جهت باد همچنان تصمیم نبود. همین تکلیف را روشن کرد.',
                        'participants' => [
                            ['name' => 'نگار موسوی', 'role' => PlaytestParticipantRole::Designer, 'account' => 'negar@simorgh.test'],
                            ['name' => 'کامران دهقان', 'role' => PlaytestParticipantRole::Player, 'account' => 'kamran@simorgh.test'],
                            ['name' => 'مریم رضایی', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Gameplay, 'content' => 'همان نتیجه با انتظار کمتر. مشکل چرخه است نه تعداد بازیکن.', 'by' => 'negar@simorgh.test'],
                            ['category' => ObservationCategory::Balance, 'content' => 'نوار گرما تنها سامانهٔ واقعی است. باقی چیزها امتیازشماری‌اند که به آن وصل شده.', 'by' => 'negar@simorgh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'ده دقیقهٔ اولش را با کمال میل دوباره بازی می‌کنم. مطمئن نیستم بقیه‌اش برای چیست.', 'rating' => 2, 'by' => 'negar@simorgh.test', 'from' => 'مریم رضایی'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 3,
                'title' => 'تست کور: آیا چهار غریبه فقط از روی کتابچه یاد می‌گیرند؟',
                'objective' => 'کتابچه و دسته را بدون هیچ توضیحی به جمعی بدهیم و هر پرسشی را ثبت کنیم.',
                'hypothesis' => 'کتابچه آن‌قدر کامل هست که تنها پرسش‌ها دربارهٔ حالت‌های مرزی باشند.',
                'conclusion' => 'یک پرسش، دو بار: اینکه تعارف بین دست‌ها می‌ماند یا نه. در کتابچه هست، سه '
                    .'بخش بعد از جایی که بازیکن‌ها لازمش دارند. راه‌حل یک ویرایش است نه یک قاعده.',
                'status' => PlaytestStatus::Completed,
                'by' => 'mahsa@otagh.test',
                'planned' => 24,
                'completed' => 21,
                'sessions' => [
                    [
                        'status' => PlaytestSessionStatus::Completed,
                        'location' => 'خانهٔ زهرا، بدون حضور طراح',
                        'by' => 'mahsa@otagh.test',
                        'held' => 24,
                        'ran' => 66,
                        'notes' => 'دو بازی کامل پشت سر هم. از روی یادداشت‌های خودشان و یک گفت‌وگوی '
                            .'بیست دقیقه‌ای بعدش نوشته شد.',
                        'outcome' => 'خودشان یاد گرفتند، دو بار بازی کردند و خواستند دسته را نگه دارند. '
                            .'هر دو بازی زیر سی و شش دقیقه.',
                        'participants' => [
                            ['name' => 'زهرا امینی', 'role' => PlaytestParticipantRole::Facilitator],
                            ['name' => 'نوید حیدری', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'شیرین بهزادی', 'role' => PlaytestParticipantRole::Player],
                            ['name' => 'مریم رضایی', 'role' => PlaytestParticipantRole::Player],
                        ],
                        'observations' => [
                            ['category' => ObservationCategory::Rules, 'content' => 'پرسیدند تعارف بین دست‌ها می‌ماند یا نه، در هر دو بازی. جوابش در کتابچه زیر بخش امتیازشماری است، یعنی سه بخش بعد از جایی که لازمش داشتند.', 'by' => 'mahsa@otagh.test', 'about' => 'شیرین بهزادی'],
                            ['category' => ObservationCategory::Gameplay, 'content' => 'تا دست چهارم بازی اول، یکی عمداً پیشنهاد پایین داد تا استکان را دست کس دیگری بیندازد. هیچ‌کس این حرکت را به آن‌ها یاد نداده بود.', 'by' => 'mahsa@otagh.test', 'about' => 'نوید حیدری'],
                            ['category' => ObservationCategory::Pacing, 'content' => 'سی و چهار و سی و شش دقیقه. چیدن از لحظهٔ باز کردن جعبه زیر سه دقیقه بود.', 'by' => 'mahsa@otagh.test'],
                        ],
                        'feedback' => [
                            ['content' => 'بی‌آنکه بخواهیم دو بار بازی‌اش کردیم. می‌شود دسته را نگه داریم؟', 'rating' => 5, 'by' => 'mahsa@otagh.test', 'from' => 'زهرا امینی'],
                            ['content' => 'کسی که به پیشنهادش نمی‌رسد یک‌جوری بهترین صندلی را دارد. انتظارش را نداشتم.', 'rating' => 5, 'by' => 'mahsa@otagh.test', 'from' => 'نوید حیدری'],
                            ['content' => 'قواعد تعارف در کتابچه جای اشتباهی‌اند. باقی‌اش روشن بود.', 'rating' => 4, 'by' => 'mahsa@otagh.test', 'from' => 'شیرین بهزادی'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
