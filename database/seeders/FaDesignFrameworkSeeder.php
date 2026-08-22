<?php

namespace Database\Seeders;

/**
 * «مسیر کارگاه» — a second methodology, authored in Persian.
 *
 * Not a translation. `DesignFrameworkSeeder` is deliberately not run through the platform
 * catalogue, because a framework's wording belongs to whoever wrote that edition rather than to
 * Barkeep — and the consequence of that decision is this file: a studio working in Persian does
 * not read the English methodology in Persian, it follows a Persian one somebody wrote.
 *
 * So the shape differs too. Eight stages rather than ten, with development, production and launch
 * collected into a single «تحویل», because the workshop this was written in hands games to a small
 * local publisher and treats the three as one handover rather than as three phases.
 *
 * ## What is shared with the English edition, and why
 *
 * The writing machinery, by inheritance: both editions write the same tables in the same way, and
 * the only things that differ are `edition()` and `phases()`. What is *not* inherited is any of the
 * content.
 *
 * The `satisfied_by` facts are the same keys — `pitch`, `player_count`, `core_action` and the rest —
 * because those name columns on the design record rather than English words. A criterion that asks
 * «آیا تعداد بازیکن‌ها تعیین شده؟» is answered by the player count being decided, in any language.
 *
 * ## Addresses
 *
 * Every phase and every piece of content carries an explicit Latin `slug`. A phase's address is a
 * URL segment, and `Str::slug('ایده‌پردازی')` is `aydhprdazy` — unreadable, unguessable, and no
 * kinder to a Persian reader than to an English one. The Persian title is what people see; the
 * Latin address is what the URL and the database use.
 */
class FaDesignFrameworkSeeder extends DesignFrameworkSeeder
{
    /**
     * The framework's address.
     */
    public const SLUG = 'masir-kargah';

    /**
     * Which methodology this is.
     */
    protected function edition(): array
    {
        return [
            'slug' => self::SLUG,
            'name' => 'مسیر کارگاه',
            'description' => 'راهی هشت‌مرحله‌ای از یک جرقه تا بازی‌ای که کسی دیگر می‌تواند بازی کند. '
                .'هر مرحله می‌گوید روی چه چیزی کار کنید، از طراحی چه بپرسید، و پیش از رفتن به مرحلهٔ بعد '
                .'چه چیزی باید درست شده باشد.',
            'version' => [
                'name' => 'ویرایش نخست',
                'description' => 'همان‌طور که در کارگاه سیمرغ نوشته و به کار گرفته شد.',
            ],
        ];
    }

    /**
     * The methodology itself.
     *
     * Written to be answerable, like the English edition: a criterion a designer cannot honestly
     * grade, and a checklist item nobody can tell whether they have met, turn a methodology into
     * paperwork in any language.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function phases(): array
    {
        return [
            [
                'name' => 'جرقه',
                'slug' => 'spark',
                'description' => 'چیزی پیدا کنید که ارزش ساختن داشته باشد. در این مرحله بازی هنوز وجود ندارد '
                    .'و چیزی هدر نمی‌رود، و همین آن را ارزان‌ترین جایی می‌کند که می‌شود در آن اشتباه کرد.',
                'principles' => [
                    ['title' => 'هر ایده قولی به بازیکن است', 'slug' => 'an-idea-is-a-promise', 'body' => 'پشت هر ایده تجربه‌ای هست که کسی امیدش را دارد. آن تجربه را نام ببرید تا طراحی هدف داشته باشد؛ بی‌نام بگذاریدش و هر تصمیم بعدی حدس می‌شود.'],
                    ['title' => 'مضمون و سازوکار باید یک چیز بخواهند', 'slug' => 'theme-and-mechanism-agree', 'body' => 'بازی‌ای دربارهٔ پنهان‌کاری که سازوکارهایش به رو بازی کردن پاداش می‌دهد همیشه غلط حس می‌شود، و هیچ تصویرسازی‌ای درستش نمی‌کند.'],
                ],
                'criteria' => [
                    ['title' => 'می‌توانید در یک جمله بگویید بازی چیست؟', 'slug' => 'one-sentence', 'body' => 'نه مضمون و نه سازوکار — تجربه. اگر یک بند طول کشید، ایده هنوز چند ایده است.', 'fact' => 'pitch'],
                    ['title' => 'چرا این بازی و نه بازی مشابهی که هست؟', 'slug' => 'why-this-one', 'body' => 'هر ایده با بازی‌هایی رقابت می‌کند که هم‌اکنون هستند و هم‌اکنون تمام شده‌اند.'],
                ],
                'practices' => [
                    ['title' => 'جملهٔ یک‌خطی را بنویسید', 'slug' => 'write-the-pitch', 'body' => 'این را کامل کنید: «بازی‌ای دربارهٔ ___ که بازیکن‌ها در آن ___ می‌کنند تا ___.» آن‌قدر بازنویسی کنید تا کسی که ایده را نشنیده، درست برایتان تکرارش کند.'],
                    ['title' => 'سه بازی نام ببرید که این میان آن‌ها می‌نشیند', 'slug' => 'name-three-neighbours', 'body' => 'برای هرکدام یک خط بنویسید که چه برمی‌دارید و یک خط که چه را رد می‌کنید. این سریع‌تر و صادقانه‌تر از ادعای بی‌همتا بودن است.'],
                ],
                'prompts' => [
                    ['title' => 'تجربهٔ اصلی', 'slug' => 'core-experience', 'body' => 'بازیکن در لحظه‌ای که بازی دقیقاً همان‌طور که می‌خواهید کار می‌کند، چه باید حس کند؟'],
                    ['title' => 'دلیل بازی کردن', 'slug' => 'reason-to-play', 'body' => 'چرا کسی این را به نزدیک‌ترین بازی‌ای که همین حالا دارد ترجیح بدهد؟'],
                ],
            ],
            [
                'name' => 'قرار',
                'slug' => 'agreement',
                'description' => 'ایده را به محدودیت تبدیل کنید. تعداد بازیکن، طول بازی، سنگینی و مخاطب '
                    .'جزئیاتی نیستند که بعداً حل شوند — همین‌ها تعیین می‌کنند کدام سازوکارها اصلاً در دسترس‌اند.',
                'principles' => [
                    ['title' => 'محدودیت خودِ طراحی است، نه کاغذبازی', 'slug' => 'constraints-are-design', 'body' => 'بازی چهل‌دقیقه‌ای دو نفره و بازی دوساعتهٔ پنج نفره حتی با سازوکارهای یکسان، دو طراحی متفاوت‌اند.'],
                    ['title' => 'مخاطبی را انتخاب کنید که بتوانید بازی کردنش را ببینید', 'slug' => 'watchable-audience', 'body' => 'طراحی برای بازیکنانی که هرگز با آن‌ها تست نمی‌کنید، طراحی برای یک حدس است.'],
                ],
                'criteria' => [
                    ['title' => 'آیا تعداد بازیکن و طول بازی تعیین شده؟', 'slug' => 'count-and-length', 'body' => 'بازه اشکالی ندارد؛ «بعداً می‌بینیم» اشکال دارد، چون هر تصمیم مربوط به ریتم را عقب می‌اندازد.', 'fact' => 'player_count'],
                    ['title' => 'آیا سنگینی بازی با مخاطبش می‌خواند؟', 'slug' => 'weight-matches-audience', 'body' => 'پیچیدگی‌ای که میز تحملش نمی‌کند، پیچیدگی‌ای است که هرگز بازی نمی‌شود.', 'fact' => 'complexity'],
                ],
                'practices' => [
                    ['title' => 'محدودیت‌ها را بنویسید', 'slug' => 'write-constraints', 'body' => 'تعداد بازیکن، طول بازی، سن، سنگینی، و تقریباً اینکه ساخت یک جعبه چقدر درمی‌آید. تا وقتی طراحی می‌کنید جلوی چشم نگهشان دارید.'],
                    ['title' => 'میزی را که می‌خواهید توصیف کنید', 'slug' => 'describe-the-table', 'body' => 'چه کسانی بازی می‌کنند، کجا، و دور و برشان چه خبر است. بازی خانوادگی و بازی جشنواره‌ای مقدار متفاوتی از وقت مرده را تاب می‌آورند.'],
                ],
                'prompts' => [
                    ['title' => 'میزی که در نظر دارید', 'slug' => 'the-intended-table', 'body' => 'چه کسانی این را بازی می‌کنند، و یک ساعت پیش از نشستن پای میز مشغول چه بودند؟'],
                ],
                'checklists' => [
                    [
                        'title' => 'آمادگی قرار',
                        'slug' => 'agreement-readiness',
                        'description' => 'آنچه باید پیش از ساختن سامانه روی آن، تعیین شده باشد.',
                        'items' => [
                            ['title' => 'جملهٔ یک‌خطی نوشته شده', 'slug' => 'pitch-written', 'fact' => 'pitch'],
                            ['title' => 'تعداد بازیکن تعیین شده', 'slug' => 'player-count-decided', 'fact' => 'player_count'],
                            ['title' => 'طول بازی تعیین شده', 'slug' => 'play-time-decided', 'fact' => 'play_time'],
                            ['title' => 'مخاطب نام‌برده شده', 'slug' => 'audience-named', 'fact' => 'audience'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'هسته',
                'slug' => 'core',
                'description' => 'کنش، هزینه و پاداشی را پیدا کنید که تکرار می‌شود و کل بازی از آن ساخته شده '
                    .'است. اگر این به تنهایی جالب نباشد، هیچ چیزی که رویش بنا شود نجاتش نمی‌دهد.',
                'principles' => [
                    ['title' => 'هسته باید در یک نوبت فهمیده شود', 'slug' => 'understandable-in-one-turn', 'body' => 'بازیکنی که بعد از نوبت اولش نتواند بگوید دارد چه می‌کند و چرا، تمام بازی را دستورالعمل دنبال می‌کند.'],
                    ['title' => 'هر تصمیم باید پیامد معنادار داشته باشد', 'slug' => 'meaningful-consequences', 'body' => 'انتخابی که نتایجش قابل تشخیص از هم نیستند انتخاب نیست؛ رابط کاربری است.'],
                    ['title' => 'شانس باید تصمیم را سخت کند، نه بی‌ربط', 'slug' => 'luck-supports-decisions', 'body' => 'تصادفی که تصمیم را دشوار می‌کند خوب است؛ تصادفی که تصمیم را بی‌اثر می‌کند نه.'],
                ],
                'criteria' => [
                    ['title' => 'آیا تصمیم اصلی معنادار است؟', 'slug' => 'core-decision-meaningful', 'body' => 'آیا بازیکن خوب و بازیکن بی‌دقت متفاوت انتخاب می‌کنند، و آیا این در نتیجه پیداست؟'],
                    ['title' => 'آیا چرخه فهمیدنی است؟', 'slug' => 'loop-understandable', 'body' => 'آیا بازیکن تازه بعد از دیدن یک دور می‌تواند چرخهٔ کنش ← پیامد ← پاداش را توصیف کند؟'],
                    ['title' => 'آیا در تکرار بیستم هنوز جالب است؟', 'slug' => 'interesting-twentieth-time', 'body' => 'چرخه تمام بازی تکرار می‌شود. یک‌بار جالب بودن آزمون نیست.'],
                ],
                'practices' => [
                    ['title' => 'چرخه را در یک جمله بنویسید', 'slug' => 'write-the-loop', 'body' => 'بگویید بازیکن چه می‌کند، چه هزینه‌ای می‌دهد و چه پس می‌گیرد. اگر به دو جمله نیاز داشتید، شاید دو چرخه دارید.'],
                    ['title' => 'چرخه را تنها و روی کاغذ بازی کنید', 'slug' => 'play-it-alone', 'body' => 'بیست تکرار، با دست. دنبال نقطه‌ای می‌گردید که دیگر جالب نیست، و اینکه چند نوبت بعد از شروع است.'],
                    ['title' => 'راهبرد غالب را پیدا کنید', 'slug' => 'find-the-dominant-strategy', 'body' => 'سعی کنید با تکرار یک کار ببرید. اگر جواب داد، این ایراد بازی کردن نیست — طراحی دارد می‌گوید چرخه یک جواب دارد.'],
                ],
                'prompts' => [
                    ['title' => 'کنش تکرارشونده', 'slug' => 'the-repeated-action', 'body' => 'بازیکن چه کاری را بارها انجام می‌دهد، و چرا جالب می‌ماند؟'],
                    ['title' => 'جالب‌ترین تصمیم', 'slug' => 'most-interesting-decision', 'body' => 'جالب‌ترین تصمیم بازی کدام است، و بازیکن هر چند وقت یک‌بار با آن روبه‌رو می‌شود؟'],
                ],
                'checklists' => [
                    [
                        'title' => 'آمادگی هسته',
                        'slug' => 'core-readiness',
                        'description' => 'هسته پایه است. اینها باید پیش از ساختن سامانه رویش درست باشند.',
                        'items' => [
                            ['title' => 'کنش اصلی مشخص شده', 'slug' => 'core-action-identified', 'fact' => 'core_action'],
                            ['title' => 'هزینهٔ کنش اصلی مشخص شده', 'slug' => 'core-cost-identified', 'fact' => 'core_cost'],
                            ['title' => 'پاداش مشخص شده', 'slug' => 'core-reward-identified', 'fact' => 'core_reward'],
                            ['title' => 'شرط باخت مشخص شده', 'slug' => 'failure-condition-identified', 'fact' => 'failure_condition'],
                            ['title' => 'شرط برد مشخص شده', 'slug' => 'win-condition-identified', 'fact' => 'win_condition'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'سامانه‌ها',
                'slug' => 'systems',
                'description' => 'سامانه‌هایی را بسازید که چرخه لازم دارد و نه بیشتر: اقتصاد، تعامل، '
                    .'جبران عقب‌ماندگی، و شکل قوسی که از نوبت اول تا آخر کشیده می‌شود.',
                'principles' => [
                    ['title' => 'پیچیدگی باید عمق معنادار بسازد', 'slug' => 'complexity-earns-depth', 'body' => 'قاعده‌ای که کار اضافه می‌کند بی‌آنکه تصمیم اضافه کند، قاعده‌ای است برای حذف.'],
                    ['title' => 'بازیکن باید بفهمد چرا برد یا باخت', 'slug' => 'players-understand-outcomes', 'body' => 'بازی‌ای که نتیجه‌اش دلبخواهی حس می‌شود به کسی چیزی یاد نمی‌دهد و کسی را دوباره دعوت نمی‌کند.'],
                    ['title' => 'تعامل باید انتخاب بسازد، نه فقط آسیب', 'slug' => 'interaction-creates-choices', 'body' => 'اینکه بتوانی جایگاه دیگری را تحت تأثیر بگذاری خیلی کمتر از داشتن تصمیمی جالب دربارهٔ اینکه بگذاری یا نه اهمیت دارد.'],
                ],
                'criteria' => [
                    ['title' => 'آیا تعامل بین بازیکن‌ها انتخاب جالب می‌سازد؟', 'slug' => 'interaction-interesting', 'body' => 'تعاملی که همیشه درست است، یا همیشه بی‌ادبانه، تصمیم نیست.'],
                    ['title' => 'آیا وقت مرده قابل تحمل است؟', 'slug' => 'downtime-acceptable', 'body' => 'فاصلهٔ بین دو نوبت یک بازیکن را در بیشترین تعداد بازیکن اندازه بگیرید و بپرسید در آن فاصله چه می‌کند.'],
                    ['title' => 'آیا بازیکنِ عقب‌مانده هنوز می‌تواند خوب بازی کند؟', 'slug' => 'losing-player-plays-well', 'body' => 'بازی‌ای که در یک‌سوم اول تعیین تکلیف می‌شود، بازی‌ای است که بیشتر طولش تشریفات است.'],
                    ['title' => 'آیا هر زیرسامانه باربر است؟', 'slug' => 'subsystems-load-bearing', 'body' => 'هرکدام را یکی‌یکی بردارید و بپرسید چه می‌شکند. نشکستن هم یک جواب است.'],
                ],
                'practices' => [
                    ['title' => 'اقتصاد را در یک صفحه ترسیم کنید', 'slug' => 'map-the-economy', 'body' => 'هر منبع، هر مصرف، هر تبدیل. چرخه‌ای بدون مصرف، رهبرِ گریخته‌ای است که هنوز اتفاق نیفتاده.'],
                    ['title' => 'یک زیرسامانه را حذف کنید', 'slug' => 'cut-a-subsystem', 'body' => 'آن‌که کمتر از همه به آن مطمئنید را انتخاب کنید و کاملاً بردارید. بدون آن بازی کنید. نگه داشتنش بعد از آن یک تصمیم است نه یک اتفاق.'],
                    ['title' => 'قوس را بنویسید', 'slug' => 'write-the-arc', 'body' => 'نوبت اول، میانهٔ بازی و دور آخر چه فرقی دارند؟ بازی بدون قوس، صفحه‌گسترده‌ای با تایمر است.'],
                ],
                'prompts' => [
                    ['title' => 'تنش از کجا می‌آید', 'slug' => 'where-tension-comes-from', 'body' => 'چه چیزی بازیکن را در طول نوبتش ناراحت می‌کند، و آیا همان ناراحتی‌ای است که می‌خواستید؟'],
                    ['title' => 'امن‌ترین گزینه', 'slug' => 'the-safest-option', 'body' => 'اگر همهٔ بازیکن‌ها همیشه امن‌ترین گزینه را انتخاب کنند چه می‌شود؟ آن بازی هنوز ارزش بازی کردن دارد؟'],
                ],
            ],
            [
                'name' => 'نمونه',
                'slug' => 'prototype',
                'description' => 'زشت‌ترین چیزی را بسازید که بشود بازی کرد. کار نمونه این است که سریع غلط '
                    .'از آب دربیاید، و برای همین باید تغییر دادنش هیچ هزینه‌ای نداشته باشد.',
                'principles' => [
                    ['title' => 'نمونه یک آزمایش است، نه محصول', 'slug' => 'prototype-is-an-experiment', 'body' => 'وقتی که صرف تمام‌شده به نظر رساندنش می‌کنید، وقتی است که صرف سخت‌تر کردن تغییرش کرده‌اید.'],
                    ['title' => 'اگر وسط جلسه نشود عوضش کرد، زیادی صیقلی است', 'slug' => 'changeable-at-the-table', 'body' => 'بهترین نمونه‌ها همان‌جا سر میز ویرایش می‌شوند.'],
                ],
                'criteria' => [
                    ['title' => 'آیا نمونه از ابتدا تا انتها بازی می‌شود؟', 'slug' => 'playable-start-to-finish', 'body' => 'نمونه‌ای که پایان ندارد نمی‌تواند به پرسش‌های مربوط به ریتم جواب بدهد.'],
                    ['title' => 'آیا در کمتر از یک دقیقه عوض می‌شود؟', 'slug' => 'changeable-in-a-minute', 'body' => 'کاور کارت و ماژیک از یک نوبت چاپ بهتر است.'],
                ],
                'practices' => [
                    ['title' => 'نمونهٔ کاغذی بسازید', 'slug' => 'build-a-paper-prototype', 'body' => 'کارت فیش، خودکار، و هر مهره‌ای که در خانه هست. تصویرسازی نکنید.'],
                    ['title' => 'قواعد را در یک صفحه بنویسید', 'slug' => 'one-page-rules', 'body' => 'نه کتابچه — صفحه‌ای که بشود دست کسی داد و با دست اصلاحش کرد.'],
                    ['title' => 'تنها و از هر صندلی بازی کنید', 'slug' => 'play-all-seats', 'body' => 'دارید بررسی می‌کنید که بازی کار می‌کند، نه اینکه لذت‌بخش است. لذت پرسش مرحلهٔ بعد است.'],
                ],
                'prompts' => [
                    ['title' => 'پنج دقیقهٔ اول', 'slug' => 'the-first-five-minutes', 'body' => 'بازیکن در پنج دقیقهٔ اول چه یاد می‌گیرد، و چه چیزی را باید به او گفت؟'],
                ],
                'checklists' => [
                    [
                        'title' => 'آمادگی نمونه',
                        'slug' => 'prototype-readiness',
                        'description' => 'آنچه باید پیش از گذاشتن بازی جلوی کسی دیگر وجود داشته باشد.',
                        'items' => [
                            ['title' => 'چرخهٔ اصلی قابل بازی است', 'slug' => 'core-loop-playable'],
                            ['title' => 'اجزای اولیه در دسترس‌اند', 'slug' => 'components-available'],
                            ['title' => 'شرط برد پیاده شده', 'slug' => 'win-condition-implemented'],
                            ['title' => 'شرط پایان یا باخت پیاده شده', 'slug' => 'ending-condition-implemented'],
                            ['title' => 'برگهٔ یک‌صفحه‌ای قواعد نوشته شده', 'slug' => 'rules-reference-written'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'میز',
                'slug' => 'table',
                'description' => 'طراحی را جلوی آدم‌ها بگذارید و ببینید واقعاً چه چیزی درست است. هر پلی‌تست '
                    .'باید به پرسشی جواب بدهد که از پیش نوشته‌اید.',
                'principles' => [
                    ['title' => 'پلی‌تست بدون پرسش، یک شب بازی کردن است', 'slug' => 'a-test-needs-a-question', 'body' => 'هر دو ارزشمندند. فقط یکی‌شان کار طراحی است.'],
                    ['title' => 'به کاری که بازیکن‌ها می‌کنند نگاه کنید، نه چیزی که می‌گویند', 'slug' => 'watch-what-they-do', 'body' => 'بازیکن آنچه را به یاد دارد گزارش می‌کند؛ شما می‌توانید ببینید دستش به سمت چه رفت.'],
                    ['title' => 'طراح بدترین داور قواعد خودش است', 'slug' => 'designer-is-a-bad-judge', 'body' => 'شما می‌دانید قاعده چه معنایی دارد. هیچ‌کس سر میز نمی‌داند.'],
                ],
                'criteria' => [
                    ['title' => 'آیا بازیکنانی بیرون از طراحی چیزی به شما یاد داده‌اند؟', 'slug' => 'outsiders-taught-you', 'body' => 'تست کردن فقط با کسانی که در طراحی کمک کرده‌اند، توضیح را تست می‌کند نه بازی را.'],
                    ['title' => 'آیا بازیکن تازه فقط از روی برگهٔ قواعد می‌فهمد؟', 'slug' => 'rules-reference-is-enough', 'body' => 'هر بار چیزی را شفاهی توضیح دادید یادداشتش کنید: آن یک ایراد در قواعد است.'],
                    ['title' => 'آیا بازی همان‌جا که می‌خواستید تمام می‌شود؟', 'slug' => 'ends-when-intended', 'body' => 'زمان واقعی بازی را با محدودیتی که در «قرار» گذاشتید مقایسه کنید.'],
                ],
                'practices' => [
                    ['title' => 'یک پلی‌تست دونفره اجرا کنید', 'slug' => 'run-a-two-player-test', 'body' => 'دو نفر اقتصاد و تعامل را تیزتر از همه آشکار می‌کنند، و ساده‌ترین جلسه برای هماهنگ کردن است.'],
                    ['title' => 'جلسه‌ای با بیشترین تعداد بازیکن اجرا کنید', 'slug' => 'run-at-the-highest-count', 'body' => 'وقت مرده، گفت‌وگوی سر میز و اثر ترتیب نوبت فقط در سقف بازه پیدا می‌شوند.'],
                    ['title' => 'بی‌آنکه حرف بزنید آموزش بدهید', 'slug' => 'teach-without-speaking', 'body' => 'برگهٔ قواعد را بدهید و چیزی نگویید. هر پرسشی را بنویسید.'],
                ],
                'prompts' => [
                    ['title' => 'چه چیزی غافلگیرتان کرد', 'slug' => 'what-surprised-you', 'body' => 'بازیکن‌ها چه کردند که انتظارش را نداشتید، و این دربارهٔ آنچه قواعد واقعاً می‌گویند چه می‌گوید؟'],
                ],
                'checklists' => [
                    [
                        'title' => 'آمادگی پلی‌تست',
                        'slug' => 'playtest-readiness',
                        'description' => 'آنچه باید درست باشد تا یک جلسه ارزش یک شب از وقت کسی دیگر را داشته باشد.',
                        'items' => [
                            ['title' => 'پرسشی که جلسه قرار است جوابش بدهد نوشته شده', 'slug' => 'question-written-down'],
                            ['title' => 'برگهٔ قواعد به‌روز است', 'slug' => 'rules-reference-current'],
                            ['title' => 'همهٔ اجزا برای تعداد بازیکن موردنظر موجودند', 'slug' => 'components-for-the-count'],
                            ['title' => 'راهی برای ثبت مشاهده‌ها آماده است', 'slug' => 'a-way-to-record'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'بازنویسی',
                'slug' => 'rewrite',
                'description' => 'هر بار یک چیز را عوض کنید و ببینید چه کرد. بیشترِ یک طراحی همین‌جا اتفاق '
                    .'می‌افتد، و همین‌جاست که قوس به نمونه برمی‌گردد.',
                'principles' => [
                    ['title' => 'هر بار یک چیز را عوض کنید', 'slug' => 'change-one-thing', 'body' => 'دو تغییر در یک جلسه، یک نتیجه می‌دهد و هیچ اطلاعاتی.'],
                    ['title' => 'پیش از افزودن، حذف کنید', 'slug' => 'cut-before-you-add', 'body' => 'بیشتر مشکل‌های یک طراحی را چیزی ایجاد می‌کند که همین حالا هست.'],
                    ['title' => 'نسخه‌ای را که شکستید نگه دارید', 'slug' => 'keep-what-you-broke', 'body' => 'تکرار فقط وقتی برگشت‌پذیر است که بتوانید بگویید بازی قبلاً چه بود.'],
                ],
                'criteria' => [
                    ['title' => 'آیا آخرین تغییر همان کاری را کرد که انتظار داشتید؟', 'slug' => 'change-did-what-you-expected', 'body' => 'اگر نمی‌توانید بگویید، تغییر آن‌قدر جدا نبوده که بشود از آن چیزی آموخت.'],
                    ['title' => 'آیا همان مشکل‌ها هنوز گزارش می‌شوند؟', 'slug' => 'same-problems-reported', 'body' => 'شکایتی که از سه جلسه جان به در ببرد، مشکل طراحی است نه مشکل آن جمع.'],
                    ['title' => 'آیا بازی دارد ساده‌تر می‌شود؟', 'slug' => 'game-getting-simpler', 'body' => 'طراحی‌های مرحلهٔ آخر معمولاً با از دست دادن قاعده بهتر می‌شوند، نه با گرفتن قاعده.'],
                ],
                'practices' => [
                    ['title' => 'نسخهٔ تازه ثبت کنید', 'slug' => 'record-a-new-version', 'body' => 'پیش از هر تغییری یک نسخهٔ شماره‌دار ببرید، تا پلی‌تست بعدی شاهدی دربارهٔ چیزی مشخص باشد.'],
                    ['title' => 'بنویسید انتظار دارید تغییر چه کند', 'slug' => 'write-what-you-expect', 'body' => 'پیش از جلسه. مقایسهٔ آن با آنچه اتفاق افتاد، تمام ماجراست.'],
                    ['title' => 'مشاهده‌ها را از اول بخوانید', 'slug' => 'read-back-the-observations', 'body' => 'همه‌شان، به ترتیب. الگوهایی میان جلسه‌ها دیده می‌شوند که درون یک جلسه نامرئی‌اند.'],
                ],
                'prompts' => [
                    ['title' => 'مسئلهٔ حل‌نشده', 'slug' => 'the-unresolved-problem', 'body' => 'کدام مسئله را مدام عقب می‌اندازید، و رویارویی با آن همین حالا چه هزینه‌ای دارد؟'],
                ],
            ],
            [
                'name' => 'تحویل',
                'slug' => 'handover',
                'description' => 'عددها، واژه‌ها و اجزا را نهایی کنید. طراحی تعیین شده؛ حالا باید متعادل شود، '
                    .'باید کسی که هرگز شما را ندیده بتواند از رویش یاد بگیرد، و باید بشود ساختش.',
                'principles' => [
                    ['title' => 'کتابچهٔ قواعد بخشی از طراحی است', 'slug' => 'rulebook-is-design', 'body' => 'قاعده‌ای که کسی پیدایش نمی‌کند، قاعده‌ای است که وجود ندارد.'],
                    ['title' => 'هر جزء برای کسی هزینه دارد', 'slug' => 'every-component-costs', 'body' => 'یک جاسازی زیبا یعنی گران‌تر شدن. تصمیم بگیرید که ارزشش را دارد یا نه.'],
                ],
                'criteria' => [
                    ['title' => 'آیا راهبرد غالبی هست؟', 'slug' => 'is-there-a-dominant-strategy', 'body' => 'از بازیکنان باتجربه بخواهید عمداً بشکنندش. شکست خوردنشان تنها شاهدی است که به حساب می‌آید.'],
                    ['title' => 'آیا کسی می‌تواند فقط از روی کتابچه بازی را یاد بگیرد؟', 'slug' => 'learnable-from-the-book', 'body' => 'به جمعی بدهیدش که خودتان در اتاقشان نیستید.'],
                    ['title' => 'آیا فهرست اجزا نهایی و قیمت‌خورده است؟', 'slug' => 'component-list-costed', 'body' => 'تعداد، جنس، اندازه و پرداخت، با عددی کنارشان.'],
                ],
                'practices' => [
                    ['title' => 'کتابچهٔ کامل را بنویسید', 'slug' => 'write-the-rulebook', 'body' => 'شامل چیدمان، ساختار نوبت، حالت‌های مرزی و واژه‌نامه.'],
                    ['title' => 'یک پلی‌تست کور اجرا کنید', 'slug' => 'run-a-blind-test', 'body' => 'جمعی خودش را آموزش می‌دهد و بدون حضور شما بازی می‌کند. پرسش‌هایشان بعد از بازی، فهرست ایرادهای کتابچه است.'],
                    ['title' => 'مشخصات اجزا را بنویسید', 'slug' => 'write-the-component-spec', 'body' => 'هر قطعه، با ابعاد، جنس و تعداد. این سندی است که سازنده رویش قیمت می‌دهد.'],
                ],
                'prompts' => [
                    ['title' => 'ریسک باقی‌مانده', 'slug' => 'the-remaining-risk', 'body' => 'به احتمال زیاد چه چیزی در این بازی غلط است، و چطور می‌فهمیدید؟'],
                    ['title' => 'اولین چیزی که حذف می‌کنید', 'slug' => 'the-first-cut', 'body' => 'اگر قیمت ساخت بالاتر از انتظار درآمد، اول کدام جزء را حذف می‌کنید و آن با بازی چه می‌کند؟'],
                ],
                'checklists' => [
                    [
                        'title' => 'آمادگی تحویل',
                        'slug' => 'handover-readiness',
                        'description' => 'آنچه باید پیش از سپردن بازی به ساخت، تعیین شده باشد.',
                        'items' => [
                            ['title' => 'فهرست اجزا نهایی شده', 'slug' => 'component-list-final'],
                            ['title' => 'مشخصات اجزا نوشته شده', 'slug' => 'component-spec-written'],
                            ['title' => 'قیمت هر واحد برآورد شده', 'slug' => 'per-unit-cost-estimated'],
                            ['title' => 'کتابچه را کسی بیرون از پروژه بازخوانی کرده', 'slug' => 'rulebook-proofread'],
                            ['title' => 'ابعاد جعبه تعیین شده', 'slug' => 'box-dimensions-decided'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
