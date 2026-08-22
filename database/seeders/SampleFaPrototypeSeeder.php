<?php

namespace Database\Seeders;

use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

/**
 * What the Persian workshop actually put on the table, and what changed between one sitting and the
 * next.
 *
 * Outcomes are mixed here too, and for the same reason. کاروان‌سرا's season wheel worked, its
 * caravan-queue rework partly worked, its length work is still running, and بادگیر's last iteration
 * failed — which is what archived the game.
 *
 * Evidence is cited by type and identifier and never copied, so what a decision shows against an
 * attached playtest is read live and always agrees with that playtest's own screen. Storage
 * references stay Latin: they are paths rather than prose.
 */
class SampleFaPrototypeSeeder extends SamplePrototypeSeeder
{
    /**
     * What each game was played on.
     *
     * @return list<array<string, mixed>>
     */
    protected function prototypes(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 1,
                'name' => 'کاروان‌سرای کاغذی',
                'description' => 'کارت فیش برای بخش‌ها، مکعب چوبی برای سکه، و فصلی که روی نواری از '
                    .'مقوا نوشته شده بود و یکی باید یادش می‌ماند جلو ببردش.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Archived,
                'by' => 'negar@simorgh.test',
                'built' => 336,
                'versions' => [
                    [
                        'name' => 'اولین میز',
                        'description' => 'شش بخش، سه خدمه، فصل روی مقوا با هرکس که یادش می‌ماند.',
                        'by' => 'negar@simorgh.test',
                        'cut' => 336,
                        'artifacts' => [
                            ['name' => 'کارت بخش‌ها، برش اول', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/karvansara/paper/bakhsh-cards-v1.pdf', 'filed' => 336, 'metadata' => ['pages' => 2, 'cards' => 6]],
                        ],
                    ],
                    [
                        'name' => 'بخش‌های پهن‌تر',
                        'description' => 'کارت بخش‌ها دو برابر پهن شد تا چهار مهمان بی‌روی‌هم‌چیدن جا '
                            .'شوند، بعد از دو جلسه که بازیکن‌ها روی هم می‌چیدند و حسابشان از دست می‌رفت.',
                        'by' => 'bahram@simorgh.test',
                        'cut' => 262,
                        'artifacts' => [
                            ['name' => 'کارت بخش‌ها، پهن', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/karvansara/paper/bakhsh-cards-v2.pdf', 'filed' => 262, 'metadata' => ['pages' => 2, 'cards' => 6]],
                            ['name' => 'عکس میز، چهار نفره', 'type' => PrototypeArtifactType::Image, 'reference' => 'samples/karvansara/paper/miz-chahar-nafare.jpg', 'filed' => 258, 'metadata' => ['width' => 3024, 'height' => 4032]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 2,
                'name' => 'نمونهٔ گردونهٔ فصل',
                'description' => 'فصل روی نوار چاپی خودش وسط میز، با بخش‌هایی که در برابرش باز و بسته '
                    .'می‌شوند.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Active,
                'by' => 'arash@simorgh.test',
                'built' => 182,
                'versions' => [
                    [
                        'name' => 'نوار چاپی',
                        'description' => 'گردونهٔ فصل بدون علامت جهت، چون فکر نمی‌کردیم کسی به آن '
                            .'احتیاج داشته باشد.',
                        'by' => 'arash@simorgh.test',
                        'cut' => 182,
                        'artifacts' => [
                            ['name' => 'گردونهٔ فصل، چاپ اول', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/karvansara/gardune/gardune-v1.pdf', 'filed' => 182, 'metadata' => ['pages' => 1, 'size' => 'A3']],
                        ],
                    ],
                    [
                        'name' => 'با فلش جهت',
                        'description' => 'همان گردونه با یک فلش رویش. دو بازیکن بدون آن فصل را برعکس '
                            .'خوانده بودند.',
                        'by' => 'bahram@simorgh.test',
                        'cut' => 158,
                        'artifacts' => [
                            ['name' => 'گردونهٔ فصل، فلش‌دار', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/karvansara/gardune/gardune-v2.pdf', 'filed' => 158, 'metadata' => ['pages' => 1, 'size' => 'A3']],
                            ['name' => 'برگهٔ قواعد، یک صفحه', 'type' => PrototypeArtifactType::Document, 'reference' => 'samples/karvansara/gardune/ghavaed-v2.docx', 'filed' => 157, 'metadata' => ['words' => 640]],
                        ],
                    ],
                    [
                        'name' => 'سه صف کاروان',
                        'description' => 'ردیف مشترک جایش را به سه صف داد، روی همان گردونه چاپ شد تا '
                            .'جای میز دوباره بزرگ‌تر نشود.',
                        'by' => 'negar@simorgh.test',
                        'cut' => 56,
                        'artifacts' => [
                            ['name' => 'گردونه و صف کاروان‌ها', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/karvansara/gardune/gardune-va-safha-v3.pdf', 'filed' => 56, 'metadata' => ['pages' => 1, 'size' => 'A3']],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'name' => 'نسخهٔ میز مجازی',
                'description' => 'نسخهٔ دیجیتال برای تست با کسانی که در شهر نیستند، و برای زمان گرفتن '
                    .'بازی بدون کرنومتر روی میز.',
                'type' => PrototypeType::Digital,
                'status' => PrototypeStatus::Active,
                'by' => 'arash@simorgh.test',
                'built' => 50,
                'versions' => [
                    [
                        'name' => 'ساخت اول',
                        'description' => 'همه چیز از نسخهٔ کاغذی، به علاوهٔ زمان‌سنج خودکار نوبت.',
                        'by' => 'arash@simorgh.test',
                        'cut' => 50,
                        'artifacts' => [
                            ['name' => 'فایل ساخت', 'type' => PrototypeArtifactType::Build, 'reference' => 'samples/karvansara/digital/karvansara-build-1.json', 'filed' => 50, 'metadata' => ['engine' => 'Tabletop Simulator', 'build' => 1]],
                            ['name' => 'زمان نوبت‌ها، جلسه‌های ۱ تا ۶', 'type' => PrototypeArtifactType::Spreadsheet, 'reference' => 'samples/karvansara/digital/zaman-nobatha.xlsx', 'filed' => 35, 'metadata' => ['rows' => 398, 'sessions' => 6]],
                        ],
                    ],
                    [
                        'name' => 'فصل چهارم کوتاه',
                        'description' => 'فصل چهارم از پنج دور به سه دور کوتاه شد، تا در برابر سقف '
                            .'اعلام‌شده زمان گرفته شود.',
                        'by' => 'arash@simorgh.test',
                        'cut' => 16,
                        'artifacts' => [
                            ['name' => 'فایل ساخت، فصل کوتاه', 'type' => PrototypeArtifactType::Build, 'reference' => 'samples/karvansara/digital/karvansara-build-2.json', 'filed' => 16, 'metadata' => ['engine' => 'Tabletop Simulator', 'build' => 2]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'qanat',
                'version' => 1,
                'name' => 'کاریز کارت فیشی',
                'description' => 'شبکه‌ای کشیده روی مقوا و کاشی‌های حفر بریده‌شده از جعبهٔ شیرینی.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Active,
                'by' => 'arash@simorgh.test',
                'built' => 90,
                'versions' => [
                    [
                        'name' => 'یک کاریز',
                        'description' => 'یک کاریز مشترک که هر بازیکنی می‌تواند آبش را باز کند.',
                        'by' => 'arash@simorgh.test',
                        'cut' => 90,
                    ],
                    [
                        'name' => 'دو کاریز',
                        'description' => 'هر بازیکن کاریز خودش، هر دو رو باز، پس تصمیم زمان‌بندی خواندن '
                            .'حریف است نه خواندن دسته.',
                        'by' => 'arash@simorgh.test',
                        'cut' => 32,
                        'artifacts' => [
                            ['name' => 'تختهٔ کاریز، دو نفره', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/qanat/kariz-boards-v2.pdf', 'filed' => 32, 'metadata' => ['pages' => 1, 'boards' => 2]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'badgir',
                'version' => 1,
                'name' => 'شبکهٔ شهر',
                'description' => 'شبکه‌ای شش‌ضلعی روی مقوا، بادگیرها مهرهٔ شیشه‌ای، و نوار گرما با یک '
                    .'گیره روی نواری چاپی.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Archived,
                'by' => 'negar@simorgh.test',
                'built' => 364,
                'versions' => [
                    [
                        'name' => 'شبکهٔ اول',
                        'description' => 'باد از یک جهت ثابت، گرما هر دور یک گام بالا.',
                        'by' => 'negar@simorgh.test',
                        'cut' => 364,
                    ],
                    [
                        'name' => 'باد متغیر',
                        'description' => 'جهت باد هر دور عوض می‌شود، به این امید که جای گذاشتن بادگیر '
                            .'تصمیم شود.',
                        'by' => 'kamran@simorgh.test',
                        'cut' => 294,
                        'artifacts' => [
                            ['name' => 'کارت‌های جهت باد', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/badgir/jahat-bad-v2.pdf', 'filed' => 294, 'by' => 'kamran@simorgh.test', 'metadata' => ['pages' => 2, 'cards' => 16]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 1,
                'name' => 'دستهٔ چاپ‌خانگی',
                'description' => 'شصت کارت چاپ‌شده چهارتا در هر صفحه و بریده با کاتر، توی یک پاکت.',
                'type' => PrototypeType::Paper,
                'status' => PrototypeStatus::Active,
                'by' => 'mahsa@otagh.test',
                'built' => 186,
                'versions' => [
                    [
                        'name' => 'دستهٔ آشپزخانه',
                        'description' => 'چهار خال، تعارف با بردن دست به دست می‌آید.',
                        'by' => 'mahsa@otagh.test',
                        'cut' => 186,
                    ],
                    [
                        'name' => 'تعارف روی پیشنهاد',
                        'description' => 'تعارف خرج می‌شود تا پیشنهاد را عوض کند، نه اینکه با بردن دست '
                            .'به دست بیاید.',
                        'by' => 'mahsa@otagh.test',
                        'cut' => 102,
                        'artifacts' => [
                            ['name' => 'دسته، چاپ خانگی', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/chai-o-khorma/daste-v2.pdf', 'filed' => 102, 'metadata' => ['pages' => 15, 'cards' => 60]],
                        ],
                    ],
                    [
                        'name' => 'دستهٔ نسخهٔ کتابچه',
                        'description' => 'دسته‌ای که کتابچهٔ نوشته‌شده توصیفش می‌کند، با استکان روی '
                            .'مهرهٔ خودش به جای یک کارت.',
                        'by' => 'mahsa@otagh.test',
                        'cut' => 42,
                        'artifacts' => [
                            ['name' => 'دسته، نسخهٔ کتابچه', 'type' => PrototypeArtifactType::Pdf, 'reference' => 'samples/chai-o-khorma/daste-v3.pdf', 'filed' => 42, 'metadata' => ['pages' => 15, 'cards' => 60]],
                            ['name' => 'کتابچه، اولین پیش‌نویس کامل', 'type' => PrototypeArtifactType::Document, 'reference' => 'samples/chai-o-khorma/ketabche-draft-1.docx', 'filed' => 39, 'metadata' => ['words' => 2080, 'sections' => 9]],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 3,
                'name' => 'نسخهٔ جعبه‌ای',
                'description' => 'یک نمونهٔ چاپ‌شده از چاپخانه، برای تست کور و برای اطمینان از اینکه '
                    .'در جعبه بسته می‌شود.',
                'type' => PrototypeType::Physical,
                'status' => PrototypeStatus::Active,
                'by' => 'mahsa@otagh.test',
                'built' => 28,
                'versions' => [
                    [
                        'name' => 'نمونهٔ اول',
                        'description' => 'روکش کتان، پنج مهرهٔ تعارف، یک استکان چوبی.',
                        'by' => 'mahsa@otagh.test',
                        'cut' => 28,
                        'artifacts' => [
                            ['name' => 'مشخصات اجزا', 'type' => PrototypeArtifactType::Spreadsheet, 'reference' => 'samples/chai-o-khorma/moshakhasat-ajza.xlsx', 'filed' => 28, 'metadata' => ['rows' => 12, 'currency' => 'IRR']],
                            ['name' => 'عکس‌های نمونهٔ چاپی', 'type' => PrototypeArtifactType::Image, 'reference' => 'samples/chai-o-khorma/nemune-chapi.jpg', 'filed' => 27, 'metadata' => ['width' => 4032, 'height' => 3024]],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * The change log.
     *
     * @return list<array<string, mixed>>
     */
    protected function iterations(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 2,
                'prototype' => 'نمونهٔ گردونهٔ فصل',
                'prototype_version' => 1,
                'title' => 'بردن فصل روی گردونهٔ خودش',
                'objective' => 'کاری کنیم بازیکن‌ها مجبور نباشند بپرسند بخشی کِی بسته می‌شود، با گذاشتن '
                    .'فصل جایی که می‌بینندش.',
                'hypothesis' => 'فصلِ دیده‌شدنی اطلاعات خوانده می‌شود نه جزئیات قواعد، و پرسیدن تمام می‌شود.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Success,
                'summary' => 'پرسیدن بعد از یک دور تمام شد. نتیجهٔ برنامه‌ریزی‌نشده بهتر بود: بازیکن‌ها '
                    .'گردونه را تهدید خواندند و پیش از جانمایی رویش خم می‌شدند. بازی حالا دربارهٔ فصل '
                    .'است نه صرفاً زمان‌بندی‌شده با آن.',
                'by' => 'negar@simorgh.test',
                'started' => 184,
                'finished' => 148,
                'playtests' => ['آیا گردونهٔ فصل با یک نگاه خوانده می‌شود؟'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Components,
                        'title' => 'فصل از برگهٔ قواعد به گردونهٔ چاپی رفت',
                        'description' => 'نواری در قطع A3 وسط میز با نشانگری که هر دور یک گام جلو می‌رود.',
                        'reason' => 'بازیکن‌ها دو سه بار در هر دور می‌پرسیدند بخش‌ها کِی بسته می‌شوند، '
                            .'یعنی اطلاعات وجود داشت و در دسترس نبود.',
                        'by' => 'arash@simorgh.test',
                    ],
                    [
                        'category' => DesignChangeCategory::Ux,
                        'title' => 'فلش جهت به گردونه اضافه شد',
                        'description' => 'یک فلش که نشان می‌دهد نشانگر به کدام سو می‌رود.',
                        'reason' => 'دو نفر از چهار بازیکن جلسهٔ اول فصل را برعکس خواندند. هیچ‌کدام بعد '
                            .'از اضافه شدن فلش همان اشتباه را نکردند.',
                        'by' => 'bahram@simorgh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'شمردن پرسش‌ها',
                        'question' => 'بازیکن‌ها در هر دور چند بار می‌پرسند بخشی کِی بسته می‌شود؟',
                        'hypothesis' => 'گردونهٔ دیده‌شدنی بعد از دور اول تقریباً به صفر می‌رساندش.',
                        'method' => 'هر پرسش دربارهٔ زمان‌بندی بخش‌ها را به تفکیک دور، در دو جلسه با دو '
                            .'جمع متفاوت، خط بزنیم.',
                        'expected' => 'دو سه پرسش در دور اول، بعد تقریباً هیچ.',
                        'actual' => 'چهار پرسش در دور اول جلسهٔ اول، یکی در دور دوم، بعد از آن هیچ. '
                            .'جلسهٔ دوم: دو تا در دور اول و بعد هیچ.',
                        'conclusion' => 'جواب گرفت. پرسش‌ها مسئلهٔ دسترسی بودند نه مسئلهٔ فهمیدن.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'negar@simorgh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'گردونه می‌ماند',
                        'decision' => 'گردونهٔ فصل بخشی از طراحی می‌شود نه کمک‌ابزار نمونه، و برگهٔ '
                            .'قواعد بخش فصل را کاملاً از دست می‌دهد.',
                        'reason' => 'به پرسشی که برایش ساخته شده بود جواب داد و رفتار بازیکن‌ها سر میز '
                            .'را عوض کرد، که نتیجهٔ قوی‌تری است از آنچه تستش می‌کردیم.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'negar@simorgh.test',
                        'decided_by' => 'negar@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'آیا گردونهٔ فصل با یک نگاه خوانده می‌شود؟', 'description' => 'هر دو جلسهٔ پلی‌تست گردونه، با دو جمع متفاوت.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'حسین از دور سوم به بعد', 'description' => 'روشن‌ترین نشانهٔ اینکه گردونه خوانده می‌شود نه صرفاً حاضر است.'],
                            ['type' => EvidenceType::Experiment, 'reference' => 'شمردن پرسش‌ها', 'description' => 'شمار پرسش‌ها به تفکیک دور، هر دو جلسه.'],
                        ],
                    ],
                    [
                        'title' => 'فلش جهت روی هر گردونهٔ آینده چاپ شود',
                        'decision' => 'از این به بعد هر گردونهٔ فصل فلش جهت دارد و مشخصات اجزا همین را '
                            .'می‌گوید.',
                        'reason' => 'نیمی از بازیکن‌های تازهٔ جلسهٔ اول بدون آن فصل را برعکس خواندند، و '
                            .'از آن به بعد هیچ‌کس.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'bahram@simorgh.test',
                        'decided_by' => 'negar@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Observation, 'reference' => 'هر دو بازیکن تازه گردونه را ساعتگرد', 'description' => 'اشتباه خواندن، ثبت‌شده در همان جلسه‌ای که رخ داد.'],
                            ['type' => EvidenceType::Note, 'description' => 'هزینه‌ای ندارد: جوهر است روی گردونه‌ای که به‌هرحال چاپ می‌کنیم.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'prototype' => 'نسخهٔ میز مجازی',
                'prototype_version' => 1,
                'title' => 'جایگزینی ردیف مشترک با سه صف',
                'objective' => 'کاری کنیم پذیرفتن یک کاروان به عنوان گرفتنش از کسی دیگر حس شود.',
                'hypothesis' => 'سه صف دیده‌شدنی باعث می‌شوند بازیکن‌ها ببینند همسایه دنبال چیست و '
                    .'عمداً از همان صف بردارند.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Partial,
                'summary' => 'بازداشتن دقیقاً همان‌طور که امید داشتیم آمد و در هر دو جلسه بازیکن‌ها بلند '
                    .'گفتندش. در عوض فصل چهارم کندتر شد و بازی چهار نفره بیشتر از سقفش رفت، که حالا '
                    .'تکرار بعدی است.',
                'by' => 'negar@simorgh.test',
                'started' => 58,
                'finished' => 32,
                'playtests' => ['آیا سه صف کاروان تصمیم بازداشتن را واقعی می‌کند؟'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Mechanics,
                        'title' => 'ردیف مشترک کاروان‌ها جایش را به سه صف رو باز داد',
                        'description' => 'کاروان‌ها از بالای یکی از سه صف برداشته می‌شوند و هر صف از '
                            .'دستهٔ خودش پر می‌شود.',
                        'reason' => 'یک ردیف مشترک یعنی هر کاروان به یک اندازه در دسترس همه است، پس '
                            .'برداشتن یکی هرگز گرفتنش از کسی مشخص نبود.',
                        'by' => 'negar@simorgh.test',
                    ],
                    [
                        'category' => DesignChangeCategory::PlayerInteraction,
                        'title' => 'صف‌ها بین فصل‌ها بر نمی‌خورند',
                        'description' => 'چیزی که زیر کارت رویی است همان‌جا می‌ماند، پس می‌شود به صفی '
                            .'که کسی برایش می‌سازد حمله کرد.',
                        'reason' => 'بر زدن، بازی را با چند گام اضافه به همان مخزن مشترک برمی‌گرداند.',
                        'by' => 'arash@simorgh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'شمردن بازداشتن‌های عمدی',
                        'question' => 'هر چند وقت یک‌بار بازیکنی کاروانی را می‌پذیرد که نمی‌تواند '
                            .'استفاده کند، فقط برای اینکه به دیگری نرسد؟',
                        'hypothesis' => 'دست‌کم دو بار در هر بازی چهار نفره.',
                        'method' => 'هر کاروانِ پذیرفته‌شده‌ای را که پذیرنده اتاقی برایش نداشت ثبت کنیم '
                            .'و بعد از بازی از او بپرسیم چرا.',
                        'expected' => 'دو سه بار در هر بازی، بیشتر در فصل سوم و چهارم.',
                        'actual' => 'چهار بار در بازی چهار نفره و سه بار در بازی دو نفره. در دو نفره '
                            .'در هر فصل اتفاق افتاد.',
                        'conclusion' => 'جواب گرفت، و عدد دو نفره پرسش تازه‌ای است: بازداشتن دائمی شاید '
                            .'زیادی باشد.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'arash@simorgh.test',
                    ],
                    [
                        'title' => 'زمان گرفتن فصل چهارم در برابر نسخهٔ دوم',
                        'question' => 'آیا سه صف فصل چهارم را طولانی‌تر از ردیف مشترک می‌کند؟',
                        'hypothesis' => 'تفاوت قابل اندازه‌گیری ندارد: همان تعداد کارت است.',
                        'method' => 'زمان واقعی فصل چهارم روی نسخهٔ دیجیتال را با جلسه‌های ثبت‌شدهٔ '
                            .'نسخهٔ دوم مقایسه کنیم.',
                        'expected' => 'حداکثر دو دقیقه اختلاف در هر جهت.',
                        'actual' => 'فصل چهارم در چهار نفره چهار دقیقه بالا رفت. بازیکن‌ها سه صف '
                            .'می‌خوانند جایی که قبلاً یک ردیف می‌خواندند.',
                        'conclusion' => 'فرضیه غلط بود. بهای این تغییر طول نوبت است، و درست در فصلی '
                            .'می‌نشیند که از قبل هم طولانی بود.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'arash@simorgh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'سه صف می‌ماند',
                        'decision' => 'بازار سه‌صفی کاروان‌ها می‌ماند، و مسئلهٔ طول بازی به عنوان تکرار '
                            .'جداگانه‌ای رسیدگی می‌شود نه دلیلی برای برگشتن.',
                        'reason' => 'بازداشتن همان تعاملی است که بازی کم داشت و راه ارزان‌تری برایش '
                            .'پیشنهاد نشده. طول بازی پیش از این تغییر هم بیشتر از حد بود.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'negar@simorgh.test',
                        'decided_by' => 'negar@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'آیا سه صف کاروان تصمیم بازداشتن را واقعی می‌کند؟', 'description' => 'هر دو جلسه، چهار نفره و دو نفره.'],
                            ['type' => EvidenceType::Experiment, 'reference' => 'شمردن بازداشتن‌های عمدی', 'description' => 'هفت بازداشتن عمدی در دو بازی.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'مریم کاروانی را پذیرفت که هیچ اتاقی', 'description' => 'بازیکنی که همان موقع بلند می‌گوید دارد بازداشتن می‌کند.'],
                        ],
                    ],
                    [
                        'title' => 'دو نفره جداگانه بررسی شود',
                        'decision' => 'قواعد کاروان در دو نفره پرسش باز در نظر گرفته می‌شود، نه همان '
                            .'قواعد چهار نفره با آدم کمتر.',
                        'reason' => 'در دو نفره هر صف هر دور محل نزاع است، که بازداشتن را دائمی می‌کند '
                            .'به جای گاه‌به‌گاه. شاید این بازی دیگری باشد.',
                        'status' => DecisionStatus::Deferred,
                        'by' => 'negar@simorgh.test',
                        'decided_by' => 'negar@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Observation, 'reference' => 'در دو نفره هر صف هر دور', 'description' => 'ثبت‌شده در جلسهٔ دو نفرهٔ دیجیتال.'],
                            ['type' => EvidenceType::Note, 'description' => 'تا روشن شدن تکلیف طول بازی چهار نفره معلق است، چون تغییری آن‌جا ممکن است این را هم عوض کند.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'prototype' => 'نسخهٔ میز مجازی',
                'prototype_version' => 2,
                'title' => 'رساندن بازی چهار نفره به زیر هفتاد و پنج دقیقه',
                'objective' => 'بازی چهار نفره را به سقف اعلام‌شده‌اش برسانیم بدون از دست دادن '
                    .'پاداش‌های پایانی که بازیکن عقب‌مانده را در بازی نگه می‌دارند.',
                'hypothesis' => 'کوتاه کردن فصل چهارم از پنج دور به سه، پانزده دقیقه از بازی کم می‌کند '
                    .'و هیچ چیز دیگری را عوض نمی‌کند.',
                'status' => IterationStatus::InProgress,
                'by' => 'sahar@simorgh.test',
                'started' => 18,
                'playtests' => ['آیا بازی چهار نفره زیر هفتاد و پنج دقیقه تمام می‌شود؟'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Pacing,
                        'title' => 'فصل چهارم از پنج دور به سه دور کوتاه شد',
                        'description' => 'فصل هنوز به همان شکل امتیاز می‌دهد؛ فقط دو دور کمتر برای عمل '
                            .'کردن هست.',
                        'reason' => 'فصل چهارم در هر جلسهٔ زمان‌گرفته‌شده از نسخهٔ دوم به بعد، '
                            .'طولانی‌ترین و کم‌اتفاق‌ترین بخش بازی بوده.',
                        'by' => 'arash@simorgh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'زمان گرفتن بازی چهار نفره با فصل کوتاه',
                        'question' => 'آیا فصل چهارمِ سه‌دوره بازی چهار نفره را زیر هفتاد و پنج دقیقه '
                            .'می‌آورد؟',
                        'hypothesis' => 'بله، حدود پانزده دقیقه.',
                        'method' => 'سه جلسهٔ چهار نفره با زمان‌سنج نسخهٔ دیجیتال، بدون حساب کردن وقت '
                            .'توضیح قواعد.',
                        'expected' => 'شصت و شش تا هفتاد دقیقه.',
                        'actual' => 'هشتاد و دو دقیقه در تنها جلسه‌ای که تا حالا اجرا شده.',
                        'status' => ExperimentStatus::Running,
                        'by' => 'sahar@simorgh.test',
                    ],
                    [
                        'title' => 'به جایش فصل سوم را کوتاه کنیم',
                        'question' => 'آیا برداشتن دورها از فصل سوم به جای چهارم، کمتر از چیزی که '
                            .'بازیکن‌ها دوست دارند هزینه می‌دهد؟',
                        'hypothesis' => 'بازیکن‌ها از دست دادن پایان را بیشتر از دست دادن میانه حس '
                            .'می‌کنند.',
                        'method' => 'همان چیدمان چهار نفره با فصل چهارم برگشته به پنج دور و فصل سوم '
                            .'کوتاه‌شده به سه.',
                        'expected' => 'طول کل مشابه، شکایت کمتر دربارهٔ پایان.',
                        'status' => ExperimentStatus::Planned,
                        'by' => 'sahar@simorgh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'یک فصل حذف شود، نه چند دور',
                        'decision' => 'بازی از چهار فصل به سه فصل برود و پاداش‌های پایانی روی فصل سوم '
                            .'بنشینند.',
                        'reason' => 'دو دور کمتر در فصل چهارم هفت دقیقه خرید و پایان را گرفت. پانزده '
                            .'دقیقه یک فصل است، و وانمود کردن به غیر از این تا حالا دو تکرار برده.',
                        'status' => DecisionStatus::Proposed,
                        'by' => 'sahar@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'آیا بازی چهار نفره زیر هفتاد و پنج دقیقه تمام می‌شود؟', 'description' => 'تنها جلسه‌ای که تا حالا اجرا شده: هشتاد و دو دقیقه.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'دو بازیکن به دور آخر رسیدند', 'description' => 'آنچه کوتاه کردن پایان واقعاً هزینه داد.'],
                            ['type' => EvidenceType::Feedback, 'reference' => 'یک دور زودتر از آنچه باید تمام شد', 'description' => 'بازیکنی که پایان کوتاه‌شده را بریده شدن توصیف می‌کند نه باختن.'],
                            ['type' => EvidenceType::Note, 'description' => 'هنوز کسی برای نگه داشتن چهار فصل دلیل طراحی آورده، نه عادت.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'qanat',
                'version' => 2,
                'prototype' => 'کاریز کارت فیشی',
                'prototype_version' => 2,
                'title' => 'دادن کاریز جداگانه به هر بازیکن',
                'objective' => 'ببینیم دو کاریز دیده‌شدنی تصمیم آب را به خواندن حریف تبدیل می‌کند یا به '
                    .'حساب‌وکتاب.',
                'hypothesis' => 'دیدن آنچه حریف کنده، اطلاعات جالب‌تری است از دانستن اینکه چه مانده.',
                'status' => IterationStatus::InProgress,
                'by' => 'arash@simorgh.test',
                'started' => 30,
                'playtests' => ['آیا وقتی هر دو کاریز دیده می‌شود تصمیمِ آب هنوز جالب است؟'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Mechanics,
                        'title' => 'هر بازیکن یک کاریز، هر دو رو باز',
                        'description' => 'هرکس کاریز خودش را می‌کند و می‌تواند آب هرکدام را باز کند.',
                        'reason' => 'یک کاریز مشترک تصمیم را دربارهٔ دسته می‌کرد. دو کاریز دیده‌شدنی '
                            .'دربارهٔ آن یکی بازیکن می‌کندش.',
                        'by' => 'arash@simorgh.test',
                    ],
                    [
                        'category' => DesignChangeCategory::Rules,
                        'title' => 'باز کردن آبِ کاریز حریف برای شما امتیازی ندارد',
                        'description' => 'می‌توانید آبش را باز کنید، ولی فقط خودش امتیاز آنچه درمی‌آید '
                            .'را می‌گیرد.',
                        'reason' => 'بدون این، حرکت درست همیشه باز کردن مال اوست و تصمیمی نمی‌ماند.',
                        'by' => 'arash@simorgh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'کسی آب کاریز حریف را باز می‌کند؟',
                        'question' => 'آیا باز کردن کاریز حریف بی‌آنکه چیزی بگیرید تا به حال حرکت درستی '
                            .'بوده؟',
                        'hypothesis' => 'بله، وقتی کاریز او دارد از امتیاز شما جلو می‌زند.',
                        'method' => 'چهار بازی پشت سر هم، با ثبت هر باز کردن و اینکه کاریز چه کسی بود.',
                        'expected' => 'یکی دو بار در چهار بازی.',
                        'status' => ExperimentStatus::Planned,
                        'by' => 'arash@simorgh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'تکلیف ریزش را روشن کنیم',
                        'decision' => 'کاریزی که ریزش کند تا پایان بازی بسته می‌ماند و آبی که تا آن '
                            .'لحظه داده بود می‌ماند.',
                        'reason' => 'قنات از اول شرط باخت نداشته، و همان تنها چیزی است که سیاههٔ آمادگی '
                            .'هسته از آن نمی‌گذرد — و هر جلسه تا حالا با گفتن «خب بس است؟» تمام شده.',
                        'status' => DecisionStatus::Proposed,
                        'by' => 'arash@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Note, 'description' => 'پروندهٔ طراحی هنوز شرط باخت ندارد. تمام شاهد همین است و کافی است.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'badgir',
                'version' => 2,
                'prototype' => 'شبکهٔ شهر',
                'prototype_version' => 2,
                'title' => 'تصمیم کردن جای بادگیر',
                'objective' => 'کاری کنیم انتخاب محل بادگیر تصمیم باشد نه عادت.',
                'hypothesis' => 'بادی که هر دور جهتش عوض می‌شود، بادگیرِ جای اشتباه را پرهزینه می‌کند.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Failed,
                'summary' => 'بازیکن‌ها یاد گرفتند بادگیر را وسط بگذارند که در هر جهتی نسبتاً خوب است، و '
                    .'بعد از آن جهت باد فقط یک انیمیشن بود. یک قاعده اضافه شد و هیچ تصمیمی عوض نشد. '
                    .'بادگیر بعد از این بایگانی شد.',
                'by' => 'negar@simorgh.test',
                'started' => 296,
                'finished' => 270,
                'playtests' => ['آیا جهت باد جای گذاشتن بادگیر را تصمیم می‌کند؟'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Mechanics,
                        'title' => 'جهت باد هر دور عوض می‌شود',
                        'description' => 'یک کارت جهت در آغاز هر دور رو می‌شود.',
                        'reason' => 'باد ثابت یعنی یک جای درست برای بادگیر وجود دارد و بقیه غلط‌اند، '
                            .'که انتخاب نیست.',
                        'by' => 'kamran@simorgh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'شمردن بادگیرهای وسط',
                        'question' => 'چند بادگیر جایی گذاشته می‌شود که در هر جهتی نسبتاً خوب باشد؟',
                        'hypothesis' => 'باد متغیر این را کم می‌کند، چون جای وسط هیچ‌وقت بهترین نیست.',
                        'method' => 'محل هر بادگیر در یک جلسهٔ پنج نفره و یک جلسهٔ سه نفره ثبت شود.',
                        'expected' => 'کمتر از یک‌سوم بادگیرها در جای وسط.',
                        'actual' => 'بیست و دو بادگیر از بیست و نه بادگیر در جای وسط. سه بازیکن گفتند '
                            .'بعد از دور دوم دیگر به کارت جهت نگاه نکردند.',
                        'conclusion' => 'فرضیه غلط بود، و دلیلش این نیست که باد کم‌اثر است: جای وسط '
                            .'همیشه به اندازهٔ کافی خوب است، و «به اندازهٔ کافی خوب» تصمیم را می‌کشد.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'kamran@simorgh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'بادگیر را کنار بگذاریم',
                        'decision' => 'بازی بایگانی شود. نوار گرما و ایدهٔ گردونه نگه داشته شود؛ بازی '
                            .'شبکه‌سازی دور آن‌ها جواب نمی‌دهد.',
                        'reason' => 'سه تکرار همگی در همان نقطه شکست خورده‌اند، و آخری نشان داد مسئله '
                            .'چرخه است نه چیزی که به آن وصل شده. ادامه دادن هزینهٔ برگشت‌ناپذیر است.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'negar@simorgh.test',
                        'decided_by' => 'negar@simorgh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'آیا جهت باد جای گذاشتن بادگیر را تصمیم می‌کند؟', 'description' => 'هر دو جلسه، پنج نفره و سه نفره.'],
                            ['type' => EvidenceType::Experiment, 'reference' => 'شمردن بادگیرهای وسط', 'description' => 'بیست و دو از بیست و نه بادگیر در جای وسط.'],
                            ['type' => EvidenceType::Feedback, 'reference' => 'ده دقیقهٔ اول واقعاً پرتنش است', 'description' => 'بازیکنی که مسئله را دقیق‌تر از ما نام می‌برد.'],
                            ['type' => EvidenceType::Note, 'description' => 'ایدهٔ گردونه به کاروان‌سرا رسید، که بازده این پروژه است.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 2,
                'prototype' => 'دستهٔ چاپ‌خانگی',
                'prototype_version' => 2,
                'title' => 'بردن تعارف از دست‌ها به پیشنهاد',
                'objective' => 'جلوی این را بگیریم که بازیکنی که جلو افتاده، منبعی را هم به دست بیاورد '
                    .'که کمکش می‌کند جلو بماند.',
                'hypothesis' => 'تعارفی که خرج پیشنهاد می‌شود به جای اینکه با دست به دست بیاید، '
                    .'منعطف‌ترین نوبت را به بازیکن عقب‌مانده می‌دهد.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Success,
                'summary' => 'نفر جلوافتادهٔ گریخته دیگر نیست. بازی‌ها حالا با دو سه امتیاز اختلاف تمام '
                    .'می‌شوند و تصمیم تعارف پرحرف‌ترین لحظهٔ هر جلسه است.',
                'by' => 'mahsa@otagh.test',
                'started' => 108,
                'finished' => 96,
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Economy,
                        'title' => 'تعارف خرج تغییر پیشنهاد می‌شود، نه با بردن دست به دست می‌آید',
                        'description' => 'هر بازیکن دستی را با یک تعارف شروع می‌کند، و صاحب استکان با دو تا.',
                        'reason' => 'به دست آوردن تعارف با بردن دست، برای یک کار به نفر جلو دو برتری '
                            .'می‌داد.',
                        'by' => 'mahsa@otagh.test',
                    ],
                    [
                        'category' => DesignChangeCategory::Balance,
                        'title' => 'یک نوار امتیاز کامل حذف شد',
                        'description' => 'امتیاز جداگانهٔ تعارف رفت؛ تعارف فقط خرج می‌شود.',
                        'reason' => 'وقتی تعارف دیگر به دست نمی‌آید، نواری برای ثبتش داشت هیچ چیز را '
                            .'ثبت می‌کرد.',
                        'by' => 'mahsa@otagh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'اختلاف برد در ده بازی',
                        'question' => 'آیا قاعدهٔ تازهٔ تعارف فاصلهٔ نفر اول و آخر را کم می‌کند؟',
                        'hypothesis' => 'اختلاف نهایی از شش امتیاز و بیشتر به حدود سه می‌رسد.',
                        'method' => 'امتیاز نهایی ده بازی در سه، چهار و پنج نفره ثبت شود.',
                        'expected' => 'میانگین اختلاف حدود سه امتیاز.',
                        'actual' => 'میانگین اختلاف ۲٫۴ امتیاز در ده بازی، و چهار بازی در دست آخر '
                            .'تعیین تکلیف شد.',
                        'conclusion' => 'جواب گرفت. بازنویسی همان کاری را کرد که برایش بود.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'mahsa@otagh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'تعارف روی پیشنهاد، خودِ طراحی است',
                        'decision' => 'قاعدهٔ تازهٔ تعارف نگه داشته می‌شود و کتابچه دورش نوشته می‌شود.',
                        'reason' => 'نفر جلوافتادهٔ گریخته را درست کرد، یک نوار امتیاز را برداشت، و '
                            .'جالب‌ترین تصمیم بازی را ساخت.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'mahsa@otagh.test',
                        'decided_by' => 'mahsa@otagh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Experiment, 'reference' => 'اختلاف برد در ده بازی', 'description' => 'ده بازی، میانگین اختلاف ۲٫۴ امتیاز.'],
                            ['type' => EvidenceType::Note, 'description' => 'قواعد را هم کوتاه‌تر کرد، که بار دوم است این پروژه با از دست دادن چیزی بهتر می‌شود.'],
                        ],
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 3,
                'prototype' => 'نسخهٔ جعبه‌ای',
                'prototype_version' => 1,
                'title' => 'تست کور کتابچه',
                'objective' => 'ببینیم جمعی بدون هیچ توضیحی می‌تواند بازی را فقط از روی کتابچه یاد '
                    .'بگیرد و بازی کند.',
                'hypothesis' => 'کتابچه آن‌قدر کامل هست که تنها پرسش‌ها حالت‌های مرزی باشند.',
                'status' => IterationStatus::Completed,
                'outcome' => IterationOutcome::Partial,
                'summary' => 'خودشان یاد گرفتند و دو بار بازی کردند، که نتیجهٔ مهم بود. یک پرسش در هر دو '
                    .'بازی آمد — اینکه تعارف بین دست‌ها می‌ماند یا نه — و سه بخش دیرتر از جایی که '
                    .'بازیکن‌ها لازمش دارند جواب داده شده.',
                'by' => 'mahsa@otagh.test',
                'started' => 27,
                'finished' => 20,
                'playtests' => ['تست کور: آیا چهار غریبه فقط از روی کتابچه یاد می‌گیرند؟'],
                'changes' => [
                    [
                        'category' => DesignChangeCategory::Rules,
                        'title' => 'بخش تعارف پیش از بخش پیشنهاد رفت',
                        'description' => 'هیچ قاعده‌ای عوض نمی‌شود؛ ترتیب بخش‌ها عوض می‌شود.',
                        'reason' => 'هر دو بازی کور پرسیدند تعارف بین دست‌ها می‌ماند یا نه، درست همان '
                            .'جایی که داشتند پیشنهاد دادن را یاد می‌گرفتند. جواب سه بخش آن‌طرف‌تر بود.',
                        'by' => 'mahsa@otagh.test',
                    ],
                ],
                'experiments' => [
                    [
                        'title' => 'شمردن پرسش‌ها در آموزش کور',
                        'question' => 'جمعی بدون توضیح، چند پرسش را بی‌جواب می‌ماند؟',
                        'hypothesis' => 'هیچ‌کدام که جلوی بازی را بگیرد.',
                        'method' => 'از جمع بخواهیم هر پرسشی را که از روی کتاب نتوانستند جواب بدهند '
                            .'بنویسند و به جای پرسیدن، حدس بزنند.',
                        'expected' => 'یکی دو تا، همه حالت مرزی.',
                        'actual' => 'یکی، در هر دو بازی، دربارهٔ قاعده‌ای که در کتاب هست.',
                        'conclusion' => 'کتابچه کامل است و بدترتیب. این یک ویرایش است.',
                        'status' => ExperimentStatus::Completed,
                        'by' => 'mahsa@otagh.test',
                    ],
                ],
                'decisions' => [
                    [
                        'title' => 'ترتیب عوض شود، نه بازنویسی',
                        'decision' => 'بخش تعارف جابه‌جا شود و پیش از دست زدن به هر چیز دیگری در '
                            .'کتابچه، یک تست کور دیگر اجرا شود.',
                        'reason' => 'فقط یک پرسش آمد و آن هم مسئلهٔ ترتیب است. بازنویسی کتابچه‌ای که '
                            .'چهار غریبه از رویش خودشان یاد گرفتند، ریسک پس‌رفت است بی‌هیچ سودی.',
                        'status' => DecisionStatus::Accepted,
                        'by' => 'mahsa@otagh.test',
                        'decided_by' => 'mahsa@otagh.test',
                        'evidence' => [
                            ['type' => EvidenceType::Playtest, 'reference' => 'تست کور: آیا چهار غریبه فقط از روی کتابچه یاد می‌گیرند؟', 'description' => 'خود جلسه، بدون حضور طراح.'],
                            ['type' => EvidenceType::Observation, 'reference' => 'پرسیدند تعارف بین دست‌ها می‌ماند', 'description' => 'همان یک پرسش، در هر دو بازی.'],
                            ['type' => EvidenceType::Feedback, 'reference' => 'قواعد تعارف در کتابچه جای اشتباهی', 'description' => 'بازیکنی که خودش تشخیصش داد.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
