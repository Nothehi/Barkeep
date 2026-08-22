<?php

namespace Database\Seeders;

use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;

/**
 * The numbers behind three of the Persian workshop's games.
 *
 * کاروان‌سرا appears twice, for the reason the whole module exists: its numbers at v2 and its
 * numbers at v3 are different, and every playtest run against v2 is only interpretable if the v2
 * profile is still there to read. The v2 one is archived, the v3 one is active, and nothing copies
 * the newer back onto the older.
 *
 * Resource and action slugs are Latin, derived from an English name held only for that purpose. A
 * slug is an address the analyser, the scenario overrides and the snapshot comparison all match on,
 * and `Str::slug('سکه')` is `skh` — which is neither readable nor stable enough to key a comparison
 * against. The Persian name is what the screen shows.
 *
 * The profiles are imperfect on purpose here too: کاروان‌سرا's coin has no real sink and its dried
 * fruit market is a subsystem the workshop already suspects is dead weight, and قنات's draft profile
 * has an action with no cost. `BalanceAnalyser` has things to say about all of them.
 */
class SampleFaEconomySeeder extends SampleEconomySeeder
{
    /**
     * The economies themselves.
     *
     * @return list<array<string, mixed>>
     */
    protected function profiles(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 2,
                'name' => 'اقتصاد گردونهٔ فصل',
                'description' => 'عددها همان‌طور که بودند وقتی کاروان‌ها یک ردیف مشترک بودند. نگه '
                    .'داشته شده چون هر پلی‌تستی میان گردونهٔ فصل و بازنویسی کاروان‌ها با همین‌ها اجرا '
                    .'شده، و مشاهده‌های آن جلسه‌ها فقط کنار همین‌ها معنا دارند.',
                'status' => BalanceProfileStatus::Archived,
                'by' => 'arash@simorgh.test',
                'created' => 180,
                'touched' => 58,
                'resources' => [
                    ['name' => 'خدمه', 'slug' => 'khadame', 'category' => ResourceCategory::Action, 'unit' => 'نفر', 'starting' => 3, 'min' => 0, 'max' => 3, 'tradeable' => false, 'accumulative' => false, 'description' => 'برای برداشتن یک کنش فرستاده می‌شود، و با چرخش فصل برمی‌گردد.'],
                    ['name' => 'سکه', 'slug' => 'sekke', 'category' => ResourceCategory::Currency, 'unit' => 'سکه', 'starting' => 5, 'min' => 0, 'convertible' => true, 'description' => 'از بخش‌هایی که در اختیار دارید درمی‌آید، خرج خشکبار می‌شود.'],
                    ['name' => 'آذوقه', 'slug' => 'azoughe', 'category' => ResourceCategory::Material, 'unit' => 'بار', 'starting' => 0, 'min' => 0, 'max' => 12, 'convertible' => true, 'description' => 'چیزی که مهمان‌ها با آن سیر می‌شوند.'],
                    ['name' => 'آبرو', 'slug' => 'aberou', 'category' => ResourceCategory::Victory, 'unit' => 'امتیاز', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'امتیاز بازی.'],
                ],
                'flows' => [
                    ['resource' => 'خدمه', 'name' => 'بازگشت خدمه با فصل', 'type' => ResourceFlowType::Generation, 'amount' => 3, 'condition' => 'در آغاز هر فصل.'],
                    ['resource' => 'خدمه', 'name' => 'جانمایی خدمه', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'هر بار که خدمه‌ای به بخشی فرستاده می‌شود.'],
                    ['resource' => 'سکه', 'name' => 'کرایهٔ اتاق', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'هر دور، برای هر بخشی که در اختیار دارید.'],
                    ['resource' => 'آذوقه', 'name' => 'بار کاروان', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'به ازای هر کنش آشپزخانه.'],
                    ['resource' => 'آذوقه', 'name' => 'سیر کردن مهمان', 'type' => ResourceFlowType::Consumption, 'amount' => 3, 'condition' => 'به ازای هر کاروانی که پذیرایی می‌شود.'],
                    ['resource' => 'آبرو', 'name' => 'کاروان راضی', 'type' => ResourceFlowType::Reward, 'amount' => 4, 'condition' => 'به ازای هر کاروان کامل پذیرایی‌شده.'],
                ],
                'actions' => [
                    [
                        'name' => 'پذیرفتن کاروان',
                        'slug' => 'accept-a-caravan',
                        'description' => 'کاروانی را که سر راه ایستاده به کاروان‌سرا بیاورید.',
                        'costs' => [['resource' => 'خدمه', 'amount' => 1]],
                        'rewards' => [['resource' => 'آذوقه', 'amount' => 1]],
                    ],
                    [
                        'name' => 'کار در آشپزخانه',
                        'slug' => 'work-the-kitchen',
                        'description' => 'آذوقه آماده کنید.',
                        'costs' => [['resource' => 'خدمه', 'amount' => 1]],
                        'rewards' => [['resource' => 'آذوقه', 'amount' => 2]],
                    ],
                    [
                        'name' => 'پذیرایی کامل',
                        'slug' => 'serve-a-caravan',
                        'description' => 'آذوقه را به کاروانی که پذیرفته‌اید بدهید.',
                        'costs' => [['resource' => 'آذوقه', 'amount' => 3]],
                        'rewards' => [['resource' => 'آبرو', 'amount' => 4], ['resource' => 'سکه', 'amount' => 2]],
                    ],
                ],
                'variables' => [
                    ['name' => 'سکهٔ آغازین', 'slug' => 'starting-coin', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'سکه', 'value' => 5, 'min' => 0, 'max' => 12, 'step' => 1, 'unit' => 'سکه'],
                    ['name' => 'بازده آشپزخانه', 'slug' => 'kitchen-yield', 'category' => BalanceVariableCategory::Reward, 'action' => 'کار در آشپزخانه', 'value' => 2, 'min' => 1, 'max' => 4, 'step' => 1, 'unit' => 'بار'],
                    ['name' => 'آبروی هر کاروان', 'slug' => 'caravan-reputation', 'category' => BalanceVariableCategory::Reward, 'action' => 'پذیرایی کامل', 'value' => 4, 'min' => 2, 'max' => 8, 'step' => 1, 'unit' => 'امتیاز'],
                ],
                'assumptions' => [
                    ['title' => 'چهار کاروان در ردیف، انتخاب کافی است', 'description' => 'فرض شده و تست نشده. همان فرضی است که بازنویسی سه‌صفی سرانجام جایش را گرفت.', 'category' => AssumptionCategory::Complexity, 'confidence' => AssumptionConfidence::Low],
                ],
                'observations' => [
                    ['title' => 'هیچ‌وقت سر کاروان‌ها دعوا نشد', 'observation' => 'در چهار جلسه هیچ‌کس کاروانی را برای اینکه به دیگری نرسد نپذیرفت. ردیف مشترک هر کاروان را به یک اندازه در دسترس همه می‌گذارد، پس برداشتن یکی هرگز گرفتنش از کسی نیست.', 'source' => ObservationSourceType::Playtest, 'reference' => 'آیا گردونهٔ فصل با یک نگاه خوانده می‌شود؟', 'severity' => ObservationSeverity::High, 'seen' => 152],
                ],
                'snapshots' => [
                    ['name' => 'اقتصاد پیش از بازنویسی کاروان‌ها', 'description' => 'همان روزی که کار سه‌صفی شروع شد گرفته شد، تا چیزی برای مقایسه با بازنویسی باشد.', 'taken' => 59],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'name' => 'اقتصاد بازنویسی کاروان‌ها',
                'description' => 'عددهای زنده: سه صف کاروان، فصلی که خودش یک منبع شمرده‌شده است، و '
                    .'بازار خشکباری که هیچ‌کس مطمئن نیست باربر باشد.',
                'status' => BalanceProfileStatus::Active,
                'by' => 'arash@simorgh.test',
                'created' => 55,
                'touched' => 4,
                'resources' => [
                    ['name' => 'خدمه', 'slug' => 'khadame', 'category' => ResourceCategory::Action, 'unit' => 'نفر', 'starting' => 3, 'min' => 0, 'max' => 3, 'tradeable' => false, 'accumulative' => false, 'description' => 'برای برداشتن یک کنش فرستاده می‌شود. با چرخش فصل برمی‌گردد و هرگز به فصل بعد منتقل نمی‌شود.'],
                    ['name' => 'سکه', 'slug' => 'sekke', 'category' => ResourceCategory::Currency, 'unit' => 'سکه', 'starting' => 5, 'min' => 0, 'convertible' => true, 'description' => 'از بخش‌های در اختیار درمی‌آید. تنها مصرفش بازار خشکبار است، و برای همین روی هم می‌ماند.'],
                    ['name' => 'آذوقه', 'slug' => 'azoughe', 'category' => ResourceCategory::Material, 'unit' => 'بار', 'starting' => 0, 'min' => 0, 'max' => 12, 'convertible' => true, 'description' => 'چیزی که مهمان‌ها با آن سیر می‌شوند، و ارزی که پذیرایی واقعاً با آن پرداخت می‌شود.'],
                    ['name' => 'علوفه', 'slug' => 'alufe', 'category' => ResourceCategory::Material, 'unit' => 'بسته', 'starting' => 2, 'min' => 0, 'max' => 8, 'convertible' => true, 'description' => 'کالای دوم؛ در بازار مبادله می‌شود یا خرج شاگرد می‌شود.'],
                    ['name' => 'کاروان', 'slug' => 'caravan', 'category' => ResourceCategory::Progression, 'unit' => 'کاروان', 'starting' => 0, 'min' => 0, 'max' => 6, 'tradeable' => false, 'description' => 'کاروانی که پذیرفته‌اید و هنوز پذیرایی نشده.'],
                    ['name' => 'گام فصل', 'slug' => 'season-step', 'category' => ResourceCategory::Capacity, 'unit' => 'گام', 'starting' => 0, 'min' => 0, 'max' => 20, 'tradeable' => false, 'spendable' => false, 'description' => 'اینکه فصل چقدر رفته. ساعتی که هر عدد دیگری در برابرش خوانده می‌شود.'],
                    ['name' => 'آبرو', 'slug' => 'aberou', 'category' => ResourceCategory::Victory, 'unit' => 'امتیاز', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'امتیاز بازی.'],
                ],
                'flows' => [
                    ['resource' => 'خدمه', 'name' => 'بازگشت خدمه با فصل', 'type' => ResourceFlowType::Generation, 'amount' => 3, 'condition' => 'در آغاز هر فصل.'],
                    ['resource' => 'خدمه', 'name' => 'جانمایی خدمه', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'هر بار که خدمه‌ای به بخشی فرستاده می‌شود.'],
                    ['resource' => 'سکه', 'name' => 'کرایهٔ اتاق', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'هر دور، برای هر بخشی که در اختیار دارید.'],
                    ['resource' => 'سکه', 'name' => 'مزد شاگرد', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'به ازای هر شاگرد، در پایان هر فصل.'],
                    ['resource' => 'آذوقه', 'name' => 'بار کاروان', 'type' => ResourceFlowType::Generation, 'amount' => 2, 'condition' => 'به ازای هر کنش آشپزخانه.'],
                    ['resource' => 'آذوقه', 'name' => 'سیر کردن مهمان', 'type' => ResourceFlowType::Consumption, 'amount' => 3, 'condition' => 'به ازای هر کاروانی که پذیرایی می‌شود.'],
                    ['resource' => 'آذوقه', 'name' => 'فاسد شدن', 'type' => ResourceFlowType::Loss, 'amount' => 1, 'condition' => 'هر آذوقه‌ای بالای آستانهٔ فساد در پایان فصل. تا حالا در بازی واقعی رخ نداده.'],
                    ['resource' => 'علوفه', 'name' => 'مبادله در بازار', 'type' => ResourceFlowType::Conversion, 'amount' => 2, 'condition' => 'یک بسته علوفه دو سکه می‌شود، یک بار در هر دور.'],
                    ['resource' => 'گام فصل', 'name' => 'پیش رفتن فصل', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'یک بار در هر دور، بی‌آنکه بشود از قلم انداختش.'],
                    ['resource' => 'آبرو', 'name' => 'کاروان راضی', 'type' => ResourceFlowType::Reward, 'amount' => 4, 'condition' => 'به ازای هر کاروان کامل پذیرایی‌شده.'],
                    ['resource' => 'آبرو', 'name' => 'مهمان گرسنه', 'type' => ResourceFlowType::Penalty, 'amount' => 2, 'condition' => 'به ازای هر کاروانی که سیر نشده می‌رود.'],
                ],
                'actions' => [
                    [
                        'name' => 'پذیرفتن کاروان',
                        'slug' => 'accept-a-caravan',
                        'description' => 'کاروانی را از بالای یکی از سه صف بردارید.',
                        'costs' => [['resource' => 'خدمه', 'amount' => 1]],
                        'rewards' => [['resource' => 'کاروان', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::Block, 'target' => 'همان صف، تا پایان دور', 'description' => 'همان بازداشتنی که کل بازنویسی برایش بود: صفی که از آن برداشته شده، صفی است که کسی دیگر این دور نمی‌خواندش.'],
                        ],
                    ],
                    [
                        'name' => 'کار در آشپزخانه',
                        'slug' => 'work-the-kitchen',
                        'description' => 'آذوقه آماده کنید، هر بار یک دیگ.',
                        'costs' => [['resource' => 'خدمه', 'amount' => 1], ['resource' => 'سکه', 'amount' => 1]],
                        'rewards' => [['resource' => 'آذوقه', 'amount' => 2]],
                    ],
                    [
                        'name' => 'باز کردن اتاق',
                        'slug' => 'open-a-room',
                        'description' => 'اتاقی را برای مهمان‌های امشب آماده کنید.',
                        'costs' => [['resource' => 'خدمه', 'amount' => 1]],
                        'rewards' => [['resource' => 'آذوقه', 'amount' => 2, 'min' => 1, 'max' => 3]],
                        'effects' => [
                            ['type' => ActionEffectType::Unlock, 'target' => 'آشپزخانهٔ همان اتاق', 'value' => 1, 'description' => 'اتاق باز است که آشپزخانه‌اش را اصلاً قابل استفاده می‌کند.'],
                        ],
                    ],
                    [
                        'name' => 'پذیرایی کامل',
                        'slug' => 'serve-a-caravan',
                        'description' => 'آذوقه را به کاروانی که پذیرفته‌اید بدهید.',
                        'costs' => [['resource' => 'آذوقه', 'amount' => 3, 'min' => 2, 'max' => 4]],
                        'rewards' => [['resource' => 'آبرو', 'amount' => 4], ['resource' => 'سکه', 'amount' => 2]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'کاروان', 'value' => -1, 'description' => 'کاروان پذیرایی‌شده از منطقهٔ بازیکن بیرون می‌رود.'],
                        ],
                    ],
                    [
                        'name' => 'گرفتن شاگرد',
                        'slug' => 'hire-a-hand',
                        'description' => 'یک جفت دست دیگر تا پایان بازی.',
                        'costs' => [['resource' => 'سکه', 'amount' => 4], ['resource' => 'علوفه', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::CapacityModifier, 'target' => 'خدمهٔ در دسترس در هر فصل', 'value' => 1, 'description' => 'تنها چیزی که سکه واقعاً به کارش می‌آید، و همان زیرسامانه‌ای که کسی مطمئن نیست باربر باشد.'],
                        ],
                    ],
                    [
                        'name' => 'جلو بردن فصل',
                        'slug' => 'turn-the-season',
                        'description' => 'فصل را یک گام زودتر جلو ببرید و بخش‌ها را پیش از موعد ببندید.',
                        'costs' => [['resource' => 'خدمه', 'amount' => 1]],
                        'rewards' => [['resource' => 'گام فصل', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::Block, 'target' => 'بخش‌های سمت رفتنِ فصل', 'description' => 'بدجنسانه، گاهی درست، و پرحرف‌ترین کنش بازی.'],
                        ],
                    ],
                ],
                'variables' => [
                    ['name' => 'سکهٔ آغازین', 'slug' => 'starting-coin', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'سکه', 'value' => 5, 'min' => 0, 'max' => 12, 'step' => 1, 'unit' => 'سکه'],
                    ['name' => 'خدمهٔ هر بازیکن', 'slug' => 'crew-per-player', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'خدمه', 'value' => 3, 'min' => 2, 'max' => 5, 'step' => 1, 'unit' => 'نفر'],
                    ['name' => 'بازده آشپزخانه', 'slug' => 'kitchen-yield', 'category' => BalanceVariableCategory::Reward, 'action' => 'کار در آشپزخانه', 'value' => 2, 'min' => 1, 'max' => 4, 'step' => 1, 'unit' => 'بار'],
                    ['name' => 'آذوقهٔ لازم هر کاروان', 'slug' => 'caravan-supply-cost', 'category' => BalanceVariableCategory::Cost, 'action' => 'پذیرایی کامل', 'value' => 3, 'min' => 2, 'max' => 5, 'step' => 1, 'unit' => 'بار'],
                    ['name' => 'آبروی هر کاروان', 'slug' => 'caravan-reputation', 'category' => BalanceVariableCategory::Reward, 'action' => 'پذیرایی کامل', 'value' => 4, 'min' => 2, 'max' => 8, 'step' => 1, 'unit' => 'امتیاز'],
                    ['name' => 'کرایهٔ هر بخش', 'slug' => 'rent-per-room', 'category' => BalanceVariableCategory::Production, 'resource' => 'سکه', 'value' => 2, 'min' => 0, 'max' => 4, 'step' => 1, 'unit' => 'سکه'],
                    ['name' => 'هزینهٔ شاگرد', 'slug' => 'hand-cost', 'category' => BalanceVariableCategory::Cost, 'action' => 'گرفتن شاگرد', 'value' => 4, 'min' => 2, 'max' => 8, 'step' => 1, 'unit' => 'سکه'],
                    ['name' => 'آستانهٔ فساد آذوقه', 'slug' => 'spoilage-threshold', 'category' => BalanceVariableCategory::Threshold, 'resource' => 'آذوقه', 'value' => 8, 'min' => 4, 'max' => 12, 'step' => 1, 'unit' => 'بار', 'description' => 'آذوقهٔ بالای این عدد در پایان فصل فاسد می‌شود. هیچ جلسه‌ای تا حالا به آن نرسیده.'],
                    ['name' => 'دور در هر فصل', 'slug' => 'rounds-per-season', 'category' => BalanceVariableCategory::Timing, 'value' => 5, 'min' => 3, 'max' => 6, 'step' => 1, 'unit' => 'دور'],
                    ['name' => 'فصل در هر بازی', 'slug' => 'seasons-per-game', 'category' => BalanceVariableCategory::Timing, 'value' => 4, 'min' => 3, 'max' => 5, 'step' => 1, 'unit' => 'فصل', 'description' => 'همان عددی که تکرارِ طول بازی پیشنهاد می‌کند به سه برسد.'],
                    ['name' => 'جریمهٔ مهمان گرسنه', 'slug' => 'hungry-guest-penalty', 'category' => BalanceVariableCategory::Cost, 'resource' => 'آبرو', 'value' => 2, 'min' => 0, 'max' => 5, 'step' => 1, 'unit' => 'امتیاز'],
                    ['name' => 'احتمال رسیدن کاروان پربار', 'slug' => 'laden-caravan-chance', 'category' => BalanceVariableCategory::Probability, 'value' => '0.650000', 'min' => '0.000000', 'max' => '1.000000', 'step' => '0.050000', 'description' => 'از روی دستهٔ کاروان‌ها خوانده می‌شود نه تاس. این‌جا نگه داشته شده چون کل اقتصاد در برابرش اندازه گرفته شده.'],
                ],
                'scenarios' => [
                    [
                        'name' => 'دو نفره',
                        'description' => 'فشرده‌ترین حالت: هر صف هر دور محل نزاع است، پس فصل کوتاه‌تر '
                            .'است و آذوقه زودتر فاسد می‌شود.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'دور در هر فصل' => 4,
                            'آستانهٔ فساد آذوقه' => 6,
                        ],
                    ],
                    [
                        'name' => 'چهار نفره، سه فصل',
                        'description' => 'پیشنهاد تکرارِ طول بازی: یک فصل کمتر، با پاداش‌های پایانی '
                            .'بزرگ‌تر برای جبرانش.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'فصل در هر بازی' => 3,
                            'آبروی هر کاروان' => 5,
                        ],
                    ],
                    [
                        'name' => 'پنج نفره با کاروان‌سالار',
                        'description' => 'برای پلی‌تستی طرح شده که هنوز اجرا نشده. خدمهٔ کمتر برای هرکس، '
                            .'فصل بلندتر، و صندلی پنجمی که هیچ خدمه‌ای جا نمی‌دهد.',
                        'status' => BalanceScenarioStatus::Draft,
                        'overrides' => [
                            'خدمهٔ هر بازیکن' => 2,
                            'دور در هر فصل' => 6,
                        ],
                    ],
                ],
                'assumptions' => [
                    ['title' => 'تنگنا آذوقه است نه سکه', 'description' => 'هر جلسه تا حالا با بازیکنانی تمام شده که سکه‌ای داشتند و نمی‌توانستند خرجش کنند و آذوقه‌ای می‌خواستند و نداشتند. اقتصاد بر این فرض اندازه گرفته شده که همین کمیابیِ جالب است.', 'category' => AssumptionCategory::Economy, 'confidence' => AssumptionConfidence::High],
                    ['title' => 'بازیکن‌ها بیشتر سکه‌شان را خرج شاگرد می‌کنند', 'description' => 'بازار خشکبار برای همین است. در هیچ جلسهٔ ثبت‌شده‌ای اتفاق نیفتاده.', 'category' => AssumptionCategory::Economy, 'confidence' => AssumptionConfidence::Low],
                    ['title' => 'فصل پنج دور است چون چهار دور عجولانه حس می‌شود', 'description' => 'هرگز مستقیم تست نشده. کار روی طول بازی دارد ناخواسته تستش می‌کند.', 'category' => AssumptionCategory::Pacing, 'confidence' => AssumptionConfidence::Low],
                    ['title' => 'بازیکن‌ها پیش از جانمایی گردونه را می‌خوانند', 'description' => 'در چهار جلسه مستقیماً دیده شده: بازیکن‌ها پیش از انتخاب روی گردونه خم می‌شوند.', 'category' => AssumptionCategory::PlayerBehaviour, 'confidence' => AssumptionConfidence::High],
                    ['title' => 'پاداش‌های پایانی برای جبران عقب‌ماندگی کافی‌اند', 'description' => 'دو جلسه با اختلاف کمتر از چهار امتیاز از فاصلهٔ نه امتیازی تمام شده. دو تا زیاد نیست.', 'category' => AssumptionCategory::Progression, 'confidence' => AssumptionConfidence::Medium],
                    ['title' => 'شش کاروان در دست، انتخاب کافی برای چهار نفره است', 'description' => 'انتخاب شده چون روی تختهٔ بازیکن جا می‌شود، که دلیل طراحی نیست.', 'category' => AssumptionCategory::Complexity, 'confidence' => AssumptionConfidence::Medium],
                ],
                'observations' => [
                    ['title' => 'سکه بی‌جای خرج روی هم می‌ماند', 'observation' => 'بازیکن‌ها جلسهٔ چهار نفره را با نه تا چهارده سکه تمام کردند. تنها مصرفش بازار خشکبار است و سه نفر از چهار نفر اصلاً سراغش نرفتند.', 'source' => ObservationSourceType::Playtest, 'reference' => 'آیا سه صف کاروان تصمیم بازداشتن را واقعی می‌کند؟', 'severity' => ObservationSeverity::Medium, 'seen' => 43],
                    ['title' => 'فساد آذوقه هرگز فعال نشده', 'observation' => 'آستانه هشت بار است. بیشترین چیزی که کسی در نه جلسهٔ ثبت‌شده در پایان فصل نگه داشته پنج بوده.', 'source' => ObservationSourceType::Session, 'reference' => 'نه جلسه، نسخهٔ دوم و سوم', 'severity' => ObservationSeverity::Low, 'seen' => 36],
                    ['title' => 'بازی چهار نفره بیشتر از سقف اعلام‌شده طول می‌کشد', 'observation' => 'هشتاد و نه، نود و سه و هشتاد و دو دقیقه در برابر سقف هفتاد و پنج دقیقه. در پنج جلسهٔ پشت سر هم برگشته.', 'source' => ObservationSourceType::Playtest, 'reference' => 'آیا بازی چهار نفره زیر هفتاد و پنج دقیقه تمام می‌شود؟', 'severity' => ObservationSeverity::Critical, 'seen' => 8],
                    ['title' => 'پاداش پایانی در فصل کوتاه بیشتر می‌ارزد', 'observation' => 'کوتاه کردن فصل چهارم سهم پاداش‌ها از امتیاز نهایی را بالا برد، چون دورهای کمتری برای جمع کردن آذوقه ماند. کسی این را طراحی نکرده بود.', 'source' => ObservationSourceType::Calculation, 'reference' => 'زمان نوبت‌ها، جلسه‌های ۱ تا ۶', 'severity' => ObservationSeverity::High, 'seen' => 7],
                    ['title' => 'بازار خشکبار باربر نیست', 'observation' => 'دو بار برداشته و دوباره گذاشته شد، بدون اثر قابل اندازه‌گیری روی امتیاز، طول بازی، یا تصمیم‌هایی که بازیکن‌ها بعد از بازی توصیف می‌کنند.', 'source' => ObservationSourceType::Review, 'reference' => 'بازبینی سامانه‌ها، نسخهٔ سوم', 'severity' => ObservationSeverity::High, 'seen' => 29],
                    ['title' => 'بازداشتن در دو نفره دائمی است', 'observation' => 'هر صف هر دور محل نزاع است، پس پذیرفتن کاروان از تصمیمی گاه‌به‌گاه به کل نوبت تبدیل می‌شود.', 'source' => ObservationSourceType::Playtest, 'reference' => 'آیا سه صف کاروان تصمیم بازداشتن را واقعی می‌کند؟', 'severity' => ObservationSeverity::Info, 'seen' => 34],
                ],
                'snapshots' => [
                    ['name' => 'اقتصاد آن‌طور که به پلی‌تست‌های طول رفت', 'description' => 'عددهایی که جلسه‌های زمان‌گیری چهار نفره با آن‌ها اجرا شد، فریز شده پیش از آنکه چیزی در پاسخ به آن‌ها عوض شود.', 'taken' => 19],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'qanat',
                'version' => 2,
                'name' => 'اولین حساب آب',
                'description' => 'هنوز به‌زحمت یک اقتصاد است. سه منبع، دو کنش، و بدون شرط باخت — که '
                    .'دقیقاً همان چیزی است که یک پروفایل در روزی که کسی شروعش می‌کند شبیهش است.',
                'status' => BalanceProfileStatus::Draft,
                'by' => 'arash@simorgh.test',
                'created' => 30,
                'touched' => 9,
                'resources' => [
                    ['name' => 'کارگر', 'slug' => 'digger', 'category' => ResourceCategory::Action, 'unit' => 'نفر', 'starting' => 4, 'min' => 0, 'max' => 4, 'tradeable' => false, 'accumulative' => false, 'description' => 'هر گام حفر یک کارگر می‌خواهد و در پایان دور برمی‌گردد.'],
                    ['name' => 'عمق', 'slug' => 'depth', 'category' => ResourceCategory::Capacity, 'unit' => 'گام', 'starting' => 0, 'min' => 0, 'max' => 10, 'tradeable' => false, 'spendable' => false, 'description' => 'هرچه عمیق‌تر، آب بیشتر و ریزش نزدیک‌تر. از آستانه که بگذرد هرچه کنده‌اید می‌ریزد.'],
                    ['name' => 'آب', 'slug' => 'water', 'category' => ResourceCategory::Victory, 'unit' => 'جوی', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'آبی که به مزرعه می‌رسد. امتیاز، فعلاً.'],
                ],
                'flows' => [
                    ['resource' => 'کارگر', 'name' => 'بازگشت کارگرها', 'type' => ResourceFlowType::Generation, 'amount' => 4, 'condition' => 'در آغاز هر دور.'],
                    ['resource' => 'کارگر', 'name' => 'یک گام حفر', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'به ازای هر کاشی حفر گذاشته‌شده.'],
                    ['resource' => 'عمق', 'name' => 'پیشروی کاریز', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'هر کاشی حفر عمق را یک گام بالا می‌برد.'],
                    ['resource' => 'آب', 'name' => 'باز شدن آب', 'type' => ResourceFlowType::Reward, 'amount' => 1, 'condition' => 'به ازای هر گام عمق، وقتی آب باز می‌شود و کاریز نریخته باشد.'],
                ],
                'actions' => [
                    [
                        'name' => 'کندن یک گام',
                        'slug' => 'dig-a-step',
                        'description' => 'کاریز را یک کاشی جلو ببرید. کاشی گذاشته‌شده برداشته نمی‌شود.',
                        'costs' => [['resource' => 'کارگر', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'عمق', 'value' => 1, 'description' => 'هر گام، کاریز را یک قدم به ریزش نزدیک‌تر می‌کند.'],
                        ],
                    ],
                    [
                        'name' => 'باز کردن آب',
                        'slug' => 'open-the-water',
                        'description' => 'کاریز را به مزرعه وصل کنید. هرچه کنده‌اید امتیاز می‌گیرد یا '
                            .'می‌ریزد.',
                        'rewards' => [['resource' => 'آب', 'amount' => 3, 'min' => 0, 'max' => 6]],
                    ],
                ],
                'variables' => [
                    ['name' => 'کارگر آغازین', 'slug' => 'starting-diggers', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'کارگر', 'value' => 4, 'min' => 2, 'max' => 6, 'step' => 1, 'unit' => 'نفر'],
                    ['name' => 'کاشی در هر کاریز', 'slug' => 'tiles-per-channel', 'category' => BalanceVariableCategory::Capacity, 'value' => 10, 'min' => 6, 'max' => 14, 'step' => 1, 'unit' => 'کاشی'],
                    ['name' => 'عمقی که کاریز می‌ریزد', 'slug' => 'collapse-depth', 'category' => BalanceVariableCategory::Threshold, 'resource' => 'عمق', 'value' => 8, 'min' => 6, 'max' => 10, 'step' => 1, 'unit' => 'گام'],
                ],
                'assumptions' => [
                    ['title' => 'ریزش کاریز، کاریز را تا آخر بازی می‌بندد', 'description' => 'در تکرار جاری پیشنهاد شده و هنوز تصمیم نشده. تمام این پروفایل چنان اندازه گرفته شده که انگار درست است.', 'category' => AssumptionCategory::Progression, 'confidence' => AssumptionConfidence::Low],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 3,
                'name' => 'اقتصاد نسخهٔ کتابچه',
                'description' => 'اقتصاد یک بازی کارتی: چهار عدد و یک مهره، همه تعیین‌شده و همه در '
                    .'کتابچه چاپ‌شده.',
                'status' => BalanceProfileStatus::Active,
                'by' => 'mahsa@otagh.test',
                'created' => 41,
                'touched' => 20,
                'resources' => [
                    ['name' => 'تعارف', 'slug' => 'favour', 'category' => ResourceCategory::Currency, 'unit' => 'تعارف', 'starting' => 1, 'min' => 0, 'max' => 3, 'tradeable' => false, 'description' => 'خرج می‌شود تا پیشنهادی را بعد از شنیدن پیشنهاد دیگری عوض کند. هرگز وسط دست به دست نمی‌آید.'],
                    ['name' => 'دست', 'slug' => 'trick', 'category' => ResourceCategory::Progression, 'unit' => 'دست', 'starting' => 0, 'min' => 0, 'max' => 16, 'tradeable' => false, 'spendable' => false, 'description' => 'دست‌های برده‌شده در این دور، که در برابر پیشنهاد شمرده می‌شوند.'],
                    ['name' => 'استکان', 'slug' => 'ember', 'category' => ResourceCategory::Other, 'unit' => 'مهره', 'starting' => 0, 'min' => 0, 'max' => 1, 'tradeable' => false, 'description' => 'یک مهره، همیشه دست دقیقاً یک بازیکن. یک امتیاز می‌گیرد و یک تعارف می‌دهد.'],
                    ['name' => 'امتیاز', 'slug' => 'point', 'category' => ResourceCategory::Victory, 'unit' => 'امتیاز', 'starting' => 0, 'min' => 0, 'tradeable' => false, 'spendable' => false, 'description' => 'امتیاز بازی. اولین کسی که در پایان دستی به چهارده برسد می‌برد.'],
                ],
                'flows' => [
                    ['resource' => 'تعارف', 'name' => 'تعارفی که با دست پخش می‌شود', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'هر بازیکن، در آغاز هر دست.'],
                    ['resource' => 'تعارف', 'name' => 'تعارف اضافهٔ صاحب استکان', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'فقط صاحب استکان، در آغاز هر دست.'],
                    ['resource' => 'تعارف', 'name' => 'تعارف خرج‌شده روی پیشنهاد', 'type' => ResourceFlowType::Consumption, 'amount' => 1, 'condition' => 'به ازای هر گامی که پیشنهاد پس از گفته شدن جابه‌جا می‌شود.'],
                    ['resource' => 'دست', 'name' => 'بردن دست', 'type' => ResourceFlowType::Generation, 'amount' => 1, 'condition' => 'به ازای هر دستی که برده می‌شود.'],
                    ['resource' => 'امتیاز', 'name' => 'رسیدن دقیق به پیشنهاد', 'type' => ResourceFlowType::Reward, 'amount' => 3, 'condition' => 'فقط برای پیشنهادی که دقیقاً به آن رسیده باشید. بیشتر و کمتر هر دو صفر می‌گیرند.'],
                    ['resource' => 'امتیاز', 'name' => 'داشتن استکان', 'type' => ResourceFlowType::Penalty, 'amount' => 1, 'condition' => 'در پایان هر دست، به هرکس که دستش باشد.'],
                ],
                'actions' => [
                    [
                        'name' => 'پیشنهاد دادن',
                        'slug' => 'bid',
                        'description' => 'بلند بگویید این دست چند دست می‌برید.',
                        'effects' => [
                            ['type' => ActionEffectType::Block, 'target' => 'پایین آوردن پیشنهاد بدون تعارف', 'description' => 'پیشنهاد بلند گفته می‌شود و مجانی جابه‌جا نمی‌شود. تمام تنش بازی همین است.'],
                        ],
                    ],
                    [
                        'name' => 'خرج کردن تعارف',
                        'slug' => 'spend-favour',
                        'description' => 'پیشنهادتان را پس از شنیدن پیشنهاد دیگری یک گام جابه‌جا کنید.',
                        'costs' => [['resource' => 'تعارف', 'amount' => 1]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'پیشنهاد خودتان', 'value' => 1, 'description' => 'هر تعارف یک گام، بالا یا پایین.'],
                        ],
                    ],
                    [
                        'name' => 'بردن دست',
                        'slug' => 'take-a-trick',
                        'description' => 'با بلندترین کارت خالِ رو شده یا خال استکان، دست را ببرید.',
                        'rewards' => [['resource' => 'دست', 'amount' => 1]],
                    ],
                    [
                        'name' => 'امتیازشماری دست',
                        'slug' => 'score-the-hand',
                        'description' => 'دست‌ها را با پیشنهادها بسنجید، جریمهٔ استکان را بدهید و '
                            .'استکان را رد کنید.',
                        'rewards' => [['resource' => 'امتیاز', 'amount' => 3, 'min' => 0, 'max' => 3]],
                        'effects' => [
                            ['type' => ActionEffectType::ResourceModifier, 'target' => 'استکان', 'value' => 1, 'description' => 'استکان به کسی می‌رسد که بیشترین فاصله را با پیشنهادش داشته، و باید دست کسی بماند.'],
                        ],
                    ],
                ],
                'variables' => [
                    ['name' => 'تعارف آغازین', 'slug' => 'starting-favour', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'تعارف', 'value' => 1, 'min' => 0, 'max' => 3, 'step' => 1, 'unit' => 'تعارف'],
                    ['name' => 'تعارف صاحب استکان', 'slug' => 'ember-holder-favour', 'category' => BalanceVariableCategory::StartingValue, 'resource' => 'تعارف', 'value' => 2, 'min' => 1, 'max' => 3, 'step' => 1, 'unit' => 'تعارف'],
                    ['name' => 'امتیاز پیشنهاد رسیده', 'slug' => 'points-for-a-met-bid', 'category' => BalanceVariableCategory::Reward, 'action' => 'امتیازشماری دست', 'value' => 3, 'min' => 1, 'max' => 5, 'step' => 1, 'unit' => 'امتیاز'],
                    ['name' => 'جریمهٔ استکان', 'slug' => 'ember-penalty', 'category' => BalanceVariableCategory::Cost, 'resource' => 'امتیاز', 'value' => 1, 'min' => 0, 'max' => 3, 'step' => 1, 'unit' => 'امتیاز'],
                    ['name' => 'امتیاز لازم برای برد', 'slug' => 'points-to-win', 'category' => BalanceVariableCategory::Threshold, 'resource' => 'امتیاز', 'value' => 14, 'min' => 10, 'max' => 20, 'step' => 1, 'unit' => 'امتیاز'],
                    ['name' => 'کارت هر بازیکن', 'slug' => 'cards-per-player', 'category' => BalanceVariableCategory::Timing, 'value' => 12, 'min' => 8, 'max' => 16, 'step' => 1, 'unit' => 'کارت'],
                ],
                'scenarios' => [
                    [
                        'name' => 'سه نفره',
                        'description' => 'دست بزرگ‌تر برای هرکس، و استکانی که بیشتر از آنچه کسی دوست '
                            .'داشته باشد به همان نفر برمی‌گردد.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'کارت هر بازیکن' => 16,
                        ],
                    ],
                    [
                        'name' => 'پنج نفره',
                        'description' => 'دست‌های کوتاه‌تر و بازی کوتاه‌تر، چون پنج دستِ دوازده‌کارتی از '
                            .'مرز چهل دقیقه می‌گذرد.',
                        'status' => BalanceScenarioStatus::Active,
                        'overrides' => [
                            'کارت هر بازیکن' => 10,
                            'امتیاز لازم برای برد' => 12,
                        ],
                    ],
                ],
                'assumptions' => [
                    ['title' => 'نرسیدن به پیشنهاد باید قابل تحمل باشد نه کوبنده', 'description' => 'نرسیدن صفر می‌گیرد و استکان را دست شما می‌گذارد، که خودش یک تعارف پس می‌دهد. تمام جبران عقب‌ماندگی روی این ایستاده که این تقریباً سربه‌سر باشد.', 'category' => AssumptionCategory::Progression, 'confidence' => AssumptionConfidence::High],
                    ['title' => 'سه نفره پشتیبانی می‌شود نه فقط ممکن', 'description' => 'چهار جلسه، همه خوب، هیچ‌کدام بهترین بازی آن شب. برگشتن استکان به همان نفر دلیل مظنون است.', 'category' => AssumptionCategory::Interaction, 'confidence' => AssumptionConfidence::Low],
                ],
                'observations' => [
                    ['title' => 'اختلاف نهایی بعد از بازنویسی تعارف کم شد', 'observation' => 'میانگین اختلاف در ده بازی از شش امتیاز به ۲٫۴ رسید، و چهار بازی از ده تا در دست آخر تعیین تکلیف شد.', 'source' => ObservationSourceType::Calculation, 'reference' => 'اختلاف برد در ده بازی', 'severity' => ObservationSeverity::Info, 'seen' => 97],
                    ['title' => 'بازیکن‌ها عمداً پیشنهاد باختن می‌دهند', 'observation' => 'تست‌کننده‌های کور خودشان حرکت پیشنهاد پایین برای رد کردن استکان را پیدا کردند، بی‌آنکه کسی بگوید چنین چیزی هست. حالا بهترین بخش بازی است.', 'source' => ObservationSourceType::Playtest, 'reference' => 'تست کور: آیا چهار غریبه فقط از روی کتابچه یاد می‌گیرند؟', 'severity' => ObservationSeverity::Info, 'seen' => 22],
                ],
                'snapshots' => [
                    ['name' => 'نسخهٔ کتابچه، همان‌طور که چاپ شد', 'description' => 'عددهایی که کتابچهٔ نوشته‌شده توصیفشان می‌کند. از این‌جا به بعد هیچ چیزی بدون کتابچهٔ تازه عوض نمی‌شود، پس همین باید خواندنی بماند.', 'taken' => 40],
                ],
            ],
        ];
    }
}
