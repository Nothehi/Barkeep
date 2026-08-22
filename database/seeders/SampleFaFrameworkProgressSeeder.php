<?php

namespace Database\Seeders;

use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;

/**
 * Four Persian games part-way through «مسیر کارگاه».
 *
 * They follow the Persian edition rather than the English one, which is the point: a framework is
 * authored content, so which methodology a game follows is a real choice and not a consequence of
 * the reader's language setting.
 *
 * Ratings and answers are keyed by the content's Latin address — the same addresses
 * `FaDesignFrameworkSeeder` writes — so the key is stable even though everything either side of it
 * is Persian.
 */
class SampleFaFrameworkProgressSeeder extends SampleFrameworkProgressSeeder
{
    /**
     * The Persian methodology.
     */
    protected function frameworkSlug(): string
    {
        return FaDesignFrameworkSeeder::SLUG;
    }

    /**
     * Who follows it, how far, and what they have said about it.
     *
     * @return list<array<string, mixed>>
     */
    protected function adoptions(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'by' => 'negar@simorgh.test',
                'status' => GameFrameworkStatus::Active,
                'started' => 346,
                'reached' => 6,
                'ratings' => [
                    'why-this-one' => [CriterionRating::Good, 'بازی‌های جانمایی کارگر معمولاً دربارهٔ کمبود کارگرند. این یکی دربارهٔ کمبود فصل است، و گردونه تنها ساعتی است که همه باید بخوانندش.'],
                    'core-decision-meaningful' => [CriterionRating::Strong, 'بازیکن بی‌دقت هر کاروانی را می‌پذیرد؛ بازیکن خوب می‌بیند کدام مهمان تا آخر فصل سیر نمی‌شود. هر دو یک نوبت را متفاوت بازی می‌کنند و در آبرو پیداست.'],
                    'loop-understandable' => [CriterionRating::Good, 'بازیکن‌های تازه بعد از یک دور درست تعریفش می‌کنند، هرچند بیشترشان می‌گویند «تا وقتی فصل نرفته» به جای اینکه نام گردونه را ببرند.'],
                    'interesting-twentieth-time' => [CriterionRating::NeedsWork, 'تا فصل سوم می‌کشد و در چهارم شل می‌شود، وقتی کاروان‌های باقی‌مانده همان‌هایی‌اند که کسی نخواسته.'],
                    'interaction-interesting' => [CriterionRating::Good, 'گرفتن اتاقی که کسی برایش صف بسته بود تمام تعامل بازی است، و کافی است. هیچ‌کس تا حالا راهی برای آسیب مستقیم‌تر نخواسته.'],
                    'downtime-acceptable' => [CriterionRating::NeedsWork, 'در چهار نفره فاصلهٔ بین دو نوبت اواخر فصل به دو دقیقه و نیم می‌رسد. همه گردونه را نگاه می‌کنند که کمک می‌کند، ولی باز طولانی‌ترین انتظار بازی است.'],
                    'losing-player-plays-well' => [CriterionRating::Good, 'پاداش‌های فصل چهارم آن‌قدر بزرگ‌اند که بازیکن عقب‌مانده در آبرو هنوز می‌تواند دوم شود. دو جلسه با اختلاف کمتر از چهار امتیاز تمام شده.'],
                    'subsystems-load-bearing' => [CriterionRating::Weak, 'بازار خشکبار دو بار برداشته و دوباره گذاشته شده و هیچ‌بار چیزی نشکست. همین یک جواب است.'],
                    'playable-start-to-finish' => [CriterionRating::Strong, 'چهار فصل کامل با امتیازشماری، در هر جلسه از نسخهٔ دوم به بعد.'],
                    'changeable-in-a-minute' => [CriterionRating::Strong, 'بخش‌ها کارت فیش‌اند و گردونه یک نوار چاپی. عوض کردن یک بخش یعنی نوشتن رویش.'],
                    'outsiders-taught-you' => [CriterionRating::Strong, 'جمع کتابخانه یادمان داد که گردونه تهدید خوانده می‌شود نه برنامه. هیچ‌کس در کارگاه متوجه نشده بود که ما داریم برنامه می‌خوانیمش.'],
                    'rules-reference-is-enough' => [CriterionRating::NeedsWork, 'سه جمع از چهار جمع پرسیدند مهمانی که نصفه سیر شده چه می‌شود. برگه چیزی نمی‌گوید، چون تصمیمش را نگرفته بودیم.'],
                    'ends-when-intended' => [CriterionRating::NeedsWork, 'بازی دو نفره حدود پنجاه دقیقه است. چهار نفره دو بار به نود و پنج دقیقه رسیده، در برابر سقف اعلام‌شدهٔ هفتاد و پنج.'],
                ],
                'answers' => [
                    'core-experience' => 'حس شبی که تقریباً از دستت در نرفته. بازیکن باید در پایان بتواند '
                        .'بگوید کدام کاروان را رها کرد و هنوز فکر کند کار درستی کرده.',
                    'reason-to-play' => 'چون هر بازی جانمایی کارگر دیگری روی قفسه دربارهٔ کم داشتن کارگر '
                        .'است. این یکی خدمه را کافی می‌دهد و وقت را می‌گیرد.',
                    'the-intended-table' => 'چهار نفر که قبلاً یک بازی جانمایی کارگر بازی کرده‌اند، شب '
                        .'وسط هفته، با کمی بیشتر از یک ساعت وقت تا وقتی یکی باید برود. همین آخری است که '
                        .'هفتاد و پنج دقیقه را از آرزو به حد تبدیل می‌کند.',
                    'the-repeated-action' => 'خدمه‌ای بگذار، کنش آن بخش را بردار، ببین فصل یک گام رفت. '
                        .'جالب می‌ماند چون ارزش همان اصطبل بسته به اینکه چند گام مانده فرق می‌کند.',
                    'most-interesting-decision' => 'اینکه آخرین مهمان کاروانی را که تقریباً تمامش کرده‌ای '
                        .'جا بدهی، یا کاروانی را شروع کنی که اگر نکنی می‌رود. دو سه بار در هر فصل پیش '
                        .'می‌آید، یعنی هشت تا دوازده بار در بازی.',
                    'where-tension-comes-from' => 'از اینکه گردونه دیده می‌شود و نمی‌ایستد. ناراحتی قرار '
                        .'است دربارهٔ برنامه‌ریزی خودت باشد، و فعلاً حدود یک‌سومش دربارهٔ منتظر ماندن '
                        .'برای بقیه است.',
                    'the-safest-option' => 'همه آشپزخانه کار کنند و کسی کاروان پرخطر نپذیرد. آن بازی '
                        .'کسل‌کننده است و می‌بازد هم — پاداش‌های فصل چهارم فقط به کسی می‌رسد که کاروانی '
                        .'را پذیرفته که مطمئن نبوده. همین جواب موردنظر است.',
                    'the-first-five-minutes' => 'یاد می‌گیرند خدمه چیست، فصل چه می‌کند و اینکه یک بخش '
                        .'می‌تواند بسته شود. باید بهشان گفت که مهمان نصفه‌سیر آبرویش را نگه می‌دارد، چون '
                        .'برگهٔ قواعد هنوز نمی‌گوید.',
                    'what-surprised-you' => 'بازیکن‌ها با گردونه حرف می‌زنند. دو جمع پیش از انتخاب خم '
                        .'شده‌اند رویش، که روشن‌ترین شاهدی است که بردنش از برگهٔ قواعد به وسط میز درست بود.',
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'qanat',
                'by' => 'arash@simorgh.test',
                'status' => GameFrameworkStatus::Active,
                'started' => 90,
                'reached' => 3,
                'ratings' => [
                    'why-this-one' => [CriterionRating::Good, 'بیشتر بازی‌های ریسک‌پذیری از تو می‌خواهند پیش از یک اتفاق تصادفی بایستی. این یکی می‌خواهد پیش از آماده شدن حریف بایستی، که خواندن است نه شرط‌بندی.'],
                    'core-decision-meaningful' => [CriterionRating::Good, 'باز کردن زودهنگام آب یک کاشی از تو می‌گیرد و دو کاشی از او. هر دو می‌خواهند آن یکی اول باز کند، و همین بازی است.'],
                    'loop-understandable' => [CriterionRating::Strong, 'بکن یا آب را باز کن. تا حالا برای کسی دوبار توضیح داده نشده.'],
                    'interesting-twentieth-time' => [CriterionRating::NeedsWork, 'یک بازی حدود چهارده کاشی است و تا آخرش جالب می‌ماند. بیشتر از آن نمی‌ماند — که دلیلی است برای کوتاه نگه داشتن بازی، نه مسئله‌ای برای حل کردن.'],
                ],
                'answers' => [
                    'core-experience' => 'دو نفر که به جای تخته به هم نگاه می‌کنند، هر دو امیدوارند آن '
                        .'یکی پلک بزند.',
                    'the-intended-table' => 'دو نفر پای میز آشپزخانه با نیم ساعت وقت، به احتمال زیاد '
                        .'میان دو بازی بلندتر.',
                    'the-repeated-action' => 'یک کاشی حفر بگذار، یا آب را باز کن. کاشی‌هایی که همین حالا '
                        .'گذاشته‌ای تصمیم بعدی را متفاوت می‌کنند.',
                    'most-interesting-decision' => 'گذاشتن کاشی چهارم وقتی می‌دانی حریف می‌تواند نوبت '
                        .'بعد آب را باز کند. یکی دو بار در بازی، و بیشترشان را همین تعیین می‌کند.',
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'badgir',
                'by' => 'negar@simorgh.test',
                'status' => GameFrameworkStatus::Paused,
                'started' => 368,
                'reached' => 5,
                'ratings' => [
                    'why-this-one' => [CriterionRating::Weak, 'یک بازی شبکه‌سازی با نوار گرما. دو تای دیگرش پشت سر من روی قفسه‌اند و هر دو تمام شده‌اند.'],
                    'core-decision-meaningful' => [CriterionRating::NeedsWork, 'ساختن یک بادگیر دیگر تقریباً همیشه درست است تا لحظه‌ای که فاجعه‌بار غلط می‌شود، و بازیکن نمی‌تواند بگوید کدام نوبت آن لحظه است.'],
                    'loop-understandable' => [CriterionRating::Good, 'بساز، وصل کن، خنک بمان. هیچ‌وقت برای کسی توضیح لازم نداشته.'],
                    'interesting-twentieth-time' => [CriterionRating::Weak, 'از دور سوم به بعد هر بازیکن همان کار را به همان ترتیب می‌کند و تنها متغیر جهت باد است.'],
                    'interaction-interesting' => [CriterionRating::Weak, 'عملاً هیچ. پنج نفر پنج بازی جداگانه را کنار هم انجام می‌دهند.'],
                    'downtime-acceptable' => [CriterionRating::Weak, 'در پنج نفره یک بازیکن چهار دقیقه صبر می‌کند تا بفهمد محله‌اش خالی شده یا نه.'],
                    'losing-player-plays-well' => [CriterionRating::NeedsWork, 'بازیکنی که در دور اول محله‌ای از دست بدهد دیگر نمی‌رسد، و دو دور دیگر باید بنشیند.'],
                    'subsystems-load-bearing' => [CriterionRating::NeedsWork, 'جهت باد اضافه شد تا جای گذاشتن بادگیر تصمیم شود. نشد.'],
                    'playable-start-to-finish' => [CriterionRating::Good, 'سه دور و یک امتیازشماری، بی‌دردسر.'],
                    'changeable-in-a-minute' => [CriterionRating::Strong, 'کل بازی شبکه‌ای است روی مقوا. عوض کردنش یعنی یک ماژیک.'],
                ],
                'answers' => [
                    'core-experience' => 'لحظهٔ تصمیم به ساختن یک بادگیر دیگر با خشت کم. آن لحظه کار '
                        .'می‌کند. هر چیزی که دورش ساخته شده نه.',
                    'the-intended-table' => 'پنج نفر که با خودشان بدرفتاری را دوست دارند. در عمل پنج نفر '
                        .'که بیشتر شب را منتظر ماندند.',
                    'the-repeated-action' => 'یک کاشی بگذار و خشت خرج کن. هر بار همان تصمیم است، و همین '
                        .'مسئله است.',
                    'the-first-five-minutes' => 'نوار گرما را یاد می‌گیرند، و بعد یاد می‌گیرند که تنها '
                        .'چیز بازی همان است.',
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'by' => 'mahsa@otagh.test',
                'status' => GameFrameworkStatus::Active,
                'started' => 186,
                'reached' => 8,
                'ratings' => [
                    'why-this-one' => [CriterionRating::Good, 'پیشنهاد بلند گفته می‌شود و تاوان نرسیدن به آن، گرفتن استکان است — که یک کار است نه یک امتیاز منفی. هیچ بازی دست‌گیری دیگری روی قفسه‌ام باختن را به یک نقش تبدیل نمی‌کند.'],
                    'core-decision-meaningful' => [CriterionRating::Strong, 'پیشنهاد دادن یکی کمتر از آنچه می‌توانی ببری، برای اینکه استکان دست کس دیگری بیفتد، حرکتی واقعی و اغلب درست است.'],
                    'loop-understandable' => [CriterionRating::Strong, 'هرکس حکم بازی کرده باشد از دست دوم درست بازی می‌کند.'],
                    'interesting-twentieth-time' => [CriterionRating::Good, 'یک بازی نه ده دست است و استکان در بیشترشان جابه‌جا می‌شود، پس وضعیت واقعاً عوض می‌شود.'],
                    'interaction-interesting' => [CriterionRating::Strong, 'خرج کردن تعارف برای عوض کردن پیشنهاد بعد از شنیدن پیشنهاد دیگری، پرحرف‌ترین لحظهٔ هر جلسه است.'],
                    'downtime-acceptable' => [CriterionRating::Strong, 'بازی دست‌گیری است. هیچ‌کس بیش از سه کارت با نوبتش فاصله ندارد.'],
                    'losing-player-plays-well' => [CriterionRating::Good, 'استکان یک امتیاز می‌گیرد و یک تعارف می‌دهد، پس بازیکن عقب‌مانده منعطف‌ترین پیشنهاد را دارد. کل هدف بازنویسی نسخهٔ دوم همین بود.'],
                    'subsystems-load-bearing' => [CriterionRating::Good, 'تعارف، پیشنهاد و استکان. برداشتن هرکدام بازی را به عنوان طراحی تمام می‌کند.'],
                    'playable-start-to-finish' => [CriterionRating::Strong, 'دسته‌کارت چاپ‌خانگی، بازی کامل، از ماه اول.'],
                    'changeable-in-a-minute' => [CriterionRating::NeedsWork, 'دیگر نه — دسته چاپ شده و عوض کردن یک کارت یعنی چاپ دوباره. این هزینهٔ رسیدن به تحویل است.'],
                    'outsiders-taught-you' => [CriterionRating::Strong, 'چهار نفر که هرگز ندیده بودمشان دو بار بازی کردند و خواستند دسته را نگه دارند. بار دوم شاهد است.'],
                    'rules-reference-is-enough' => [CriterionRating::Good, 'یک پرسش در تست کور آخر، دربارهٔ اینکه تعارف بین دست‌ها می‌ماند یا نه. در کتابچه هست؛ در بخش اشتباهی است.'],
                    'ends-when-intended' => [CriterionRating::Strong, 'بیست و هشت تا سی و شش دقیقه در نه جلسهٔ آخر، در برابر بیست و پنج تا چهل اعلام‌شده.'],
                    'change-did-what-you-expected' => [CriterionRating::Good, 'بردن تعارف روی پیشنهاد دقیقاً همان‌طور که می‌خواستم جلوی گریختن نفر جلو را گرفت.'],
                    'same-problems-reported' => [CriterionRating::Good, 'تنها تکرار، همان پرسش تعارف بین دست‌هاست، که مشکل کتابچه است نه طراحی.'],
                    'game-getting-simpler' => [CriterionRating::Strong, 'بازنویسی نسخهٔ دوم یک نوار امتیاز کامل را حذف کرد.'],
                    'is-there-a-dominant-strategy' => [CriterionRating::Good, 'دو جمع باتجربه سعی کردند با پیشنهاد همیشه صفر بشکنندش. آرام و آشکار می‌بازد.'],
                    'learnable-from-the-book' => [CriterionRating::NeedsWork, 'تقریباً. بخش تعارف باید پیش از بخش پیشنهاد بیاید، چون بازیکن‌ها به همان ترتیب لازمش دارند.'],
                    'component-list-costed' => [CriterionRating::Strong, 'شصت کارت، پنج مهرهٔ تعارف و یک استکان چوبی. هیچ‌کدام از این فهرست نمی‌خواهد چیز دیگری باشد.'],
                ],
                'answers' => [
                    'core-experience' => 'نیم‌ثانیه بعد از اینکه کسی پیشنهاد بلندی می‌دهد و می‌فهمی '
                        .'می‌توانی نگذاری به آن برسد.',
                    'reason-to-play' => 'بازی دست‌گیری‌ای است که در آن بازنده جالب‌ترین نوبت را دارد، و '
                        .'توی جیب پالتو جا می‌شود.',
                    'the-intended-table' => 'چهار نفر در کتابخانهٔ محله با نیم ساعت وقت و یک میز کوچک. '
                        .'همان‌جا طراحی شده و اندازه‌اش برای همان است.',
                    'the-repeated-action' => 'پیشنهاد بده، بعد کارت بینداز. پیشنهاد است که همان پنجاه و '
                        .'دو کارت را هر دست مسئله‌ای تازه می‌کند.',
                    'most-interesting-decision' => 'اینکه تعارف خرج کنی تا پیشنهادی را پایین بیاوری که '
                        .'حالا می‌دانی به آن نمی‌رسی. یکی دو بار در هر دست.',
                    'where-tension-comes-from' => 'از اینکه عددی را بلند گفته‌ای که همه شنیده‌اند.',
                    'the-safest-option' => 'همه یک پیشنهاد بدهند. آن بازی ساکت است، کوتاه است و استکان '
                        .'تعیینش می‌کند، که بازی بدتری است ولی هنوز بازی است — و پایدار هم نیست، چون '
                        .'هرکس اول بشکند می‌برد.',
                    'the-first-five-minutes' => 'پیشنهاد، دست و استکان. باید بهشان گفت که تعارف بین '
                        .'دست‌ها نمی‌ماند، که تقصیر کتابچه است.',
                    'what-surprised-you' => 'بازیکن‌ها خیلی بیشتر از آنچه طراحی کرده بودم عمداً پیشنهاد '
                        .'باختن می‌دهند. معلوم شد همان بهترین بخش بازی است.',
                    'the-unresolved-problem' => 'اینکه پنج نفره واقعاً پشتیبانی می‌شود یا فقط ممکن است. '
                        .'هر جلسهٔ پنج نفره خوب بوده و هیچ‌کدام بهترین بازی آن شب نبوده.',
                    'the-remaining-risk' => 'اینکه استکان در سه نفره زیادی نوسانی است، جایی که بیش از '
                        .'حد به همان نفر برمی‌گردد. دوازده جلسهٔ سه نفره تکلیفش را روشن می‌کند و من '
                        .'چهارتا اجرا کرده‌ام.',
                    'the-first-cut' => 'مهره‌های تعارف. می‌شود با سکه یا خرما هم شمردشان، و کارت‌ها '
                        .'چیزی هستند که واقعاً چاپ باکیفیت می‌خواهند.',
                ],
            ],
        ];
    }
}
