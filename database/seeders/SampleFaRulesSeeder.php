<?php

namespace Database\Seeders;

use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Enums\TriggerType;

/**
 * The rules behind three of the Persian workshop's games.
 *
 * کاروان‌سرا appears three times, for the reason the whole module exists: its
 * rules at v2 and its rules at v3 are different, every playtest run under v2 is
 * only interpretable if the v2 set is still there to read, and the third is the
 * draft somebody cloned from the live rules — because an active rule set refuses
 * every edit and cloning is the only way forward.
 *
 * Rule, phase, mechanic and action slugs are Latin, taken from the definition
 * rather than derived. `Str::slug('برپایی', '_')` is `brpayy`: stable, and
 * unreadable to everybody. The Persian name is what the screen shows; the slug
 * is what a clone matches on when it copies a set across.
 *
 * قنات's draft is imperfect on purpose here too — an action with no phase, a
 * condition nothing points at, and no way to win — so `RuleSetValidator` has an
 * error and a handful of warnings to report rather than an empty screen.
 */
class SampleFaRulesSeeder extends SampleRulesSeeder
{
    /**
     * The rule systems themselves.
     *
     * @return list<array<string, mixed>>
     */
    protected function ruleSets(): array
    {
        return [
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 2,
                'name' => 'قواعد گردونهٔ فصل',
                'description' => 'قواعد همان‌طور که بودند وقتی کاروان‌ها یک ردیف مشترک چهارتایی بودند. نگه '
                    .'داشته شده چون هر پلی‌تستی میان گردونهٔ فصل و بازنویسی مهمان‌داری زیر همین‌ها اجرا شده، و '
                    .'مشاهده‌های آن جلسه‌ها فقط کنار همین‌ها معنا دارند.',
                'status' => RuleSetStatus::Archived,
                'by' => 'negar@simorgh.test',
                'created' => 174,
                'touched' => 60,
                'mechanics' => [
                    ['name' => 'کارگرگذاری', 'slug' => 'worker_placement', 'category' => MechanicCategory::Action, 'description' => 'سه خدمه برای هر بازیکن، یکی‌یکی روی جاهایی گذاشته می‌شوند که پشت سرشان بسته می‌شود.'],
                    ['name' => 'گردونهٔ فصل', 'slug' => 'season_wheel', 'category' => MechanicCategory::Progression, 'description' => 'ساعتی مشترک که بخش‌ها را باز و بسته می‌کند. هر عدد دیگری کنار همین خوانده می‌شود.'],
                    ['name' => 'مجموعه‌سازی', 'slug' => 'set_collection', 'category' => MechanicCategory::Scoring, 'description' => 'کاروان‌ها آذوقه را در ترکیب‌های مشخص می‌خواهند، نه به‌صورت انبوه.'],
                ],
                'phases' => [
                    ['name' => 'برپایی', 'slug' => 'setup', 'type' => GamePhaseType::Setup, 'description' => 'کاروان‌سرا را بچینید، سه کاروان رو باز کنید و به هر بازیکن سه خدمه و پنج سکه بدهید.'],
                    ['name' => 'آغاز دور', 'slug' => 'round_start', 'type' => GamePhaseType::Round, 'description' => 'گردونهٔ فصل یک گام جلو می‌رود و بخش‌ها با آن باز و بسته می‌شوند.'],
                    ['name' => 'گذاشتن خدمه', 'slug' => 'placement', 'type' => GamePhaseType::Action, 'description' => 'بازیکنان به نوبت هر بار یک خدمه می‌گذارند تا همه گذاشته شوند.'],
                    ['name' => 'فیصله', 'slug' => 'resolution', 'type' => GamePhaseType::Resolution, 'description' => 'خدمه‌های گذاشته‌شده به ترتیب کاروان‌سرا بررسی می‌شوند، نه به ترتیب نوبت.'],
                    ['name' => 'چرخش فصل', 'slug' => 'season_turn', 'type' => GamePhaseType::Cleanup, 'description' => 'همهٔ خدمه‌ها برمی‌گردند، اجارهٔ بخش‌ها پرداخت می‌شود و ردیف کاروان پر می‌شود.'],
                    ['name' => 'پایان بازی', 'slug' => 'game_end', 'type' => GamePhaseType::EndGame, 'description' => 'کاروان‌های نگه‌داشته و آبرو شمرده می‌شوند. بیشترین مجموع می‌برد.'],
                ],
                'conditions' => [
                    ['name' => 'همهٔ خدمه‌ها گذاشته شده‌اند', 'type' => ConditionType::GameState, 'operator' => ConditionOperator::IsTrue, 'description' => 'هیچ‌کس خدمه‌ای در دست ندارد.'],
                    ['name' => 'چهار فصل تمام شده است', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '4', 'description' => 'روی گردونه شمرده می‌شود، نه جداگانه.'],
                    ['name' => 'بیشترین آبروی روی میز', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThan, 'value' => '0', 'description' => 'در تساوی، کسی که کاروان‌های ناتمام کمتری دارد جلوتر است.'],
                ],
                'triggers' => [
                    ['name' => 'با چرخش فصل', 'type' => TriggerType::RoundEnd, 'description' => 'تنها لحظه‌ای در دور که چیزی خودبه‌خود رخ می‌دهد.'],
                ],
                'transitions' => [
                    ['from' => 'برپایی', 'to' => 'آغاز دور'],
                    ['from' => 'آغاز دور', 'to' => 'گذاشتن خدمه'],
                    ['from' => 'گذاشتن خدمه', 'to' => 'فیصله', 'condition' => 'همهٔ خدمه‌ها گذاشته شده‌اند'],
                    ['from' => 'فیصله', 'to' => 'چرخش فصل'],
                    ['from' => 'چرخش فصل', 'to' => 'پایان بازی', 'condition' => 'چهار فصل تمام شده است', 'trigger' => 'با چرخش فصل'],
                    ['from' => 'چرخش فصل', 'to' => 'آغاز دور', 'trigger' => 'با چرخش فصل'],
                ],
                'rules' => [
                    ['name' => 'گذاشتن خدمه', 'slug' => 'placing_crew', 'type' => RuleType::Turn, 'phase' => 'گذاشتن خدمه', 'description' => 'از بازیکن اول و در جهت عقربه‌های ساعت، هر بازیکن یک خدمه روی یک جای باز می‌گذارد. دور میز ادامه می‌یابد تا همهٔ خدمه‌ها گذاشته شوند.'],
                    ['name' => 'هر جا یک خدمه', 'slug' => 'one_crew_per_space', 'parent' => 'گذاشتن خدمه', 'type' => RuleType::Turn, 'phase' => 'گذاشتن خدمه', 'description' => 'هر جا یک خدمه می‌گیرد. وقتی گرفته شد تا آخر دور بسته است، هر کس که گرفته باشدش.'],
                    ['name' => 'خدمه با فصل برمی‌گردد', 'slug' => 'crew_returns', 'parent' => 'گذاشتن خدمه', 'type' => RuleType::Turn, 'phase' => 'چرخش فصل', 'description' => 'هر خدمه با چرخش فصل برمی‌گردد. چیزی منتقل نمی‌شود، پس خدمه‌ای که خرج نشود هدر رفته است.'],
                    ['name' => 'کاروان‌ها', 'slug' => 'caravans', 'type' => RuleType::Resource, 'description' => 'چهار کاروان رو در یک ردیف مشترک می‌ایستند. هر بازیکنی می‌تواند هر کدام را بردارد، و ردیف با چرخش فصل پر می‌شود.'],
                    ['name' => 'پذیرایی از یک کاروان', 'slug' => 'serving_a_caravan', 'parent' => 'کاروان‌ها', 'type' => RuleType::Scoring, 'phase' => 'فیصله', 'description' => 'آذوقه‌ای را که کاروان می‌خواهد تحویل بدهید تا آبرویش را بگیرید. کاروانی که نتوانید سیرش کنید در بخش شما می‌ماند و هیچ نمی‌ارزد.'],
                    ['name' => 'فصل', 'slug' => 'the_season', 'type' => RuleType::General, 'description' => 'فصل هر دور یک گام جلو می‌رود و هرگز برنمی‌گردد. بخش‌های سمت رو به پایان بسته می‌شوند، چه مهمانی در آن‌ها باشد چه نباشد.'],
                    ['name' => 'ماندن در راه', 'slug' => 'stranded', 'parent' => 'فصل', 'type' => RuleType::General, 'phase' => 'چرخش فصل', 'description' => 'کاروانی که هنگام بسته شدن بخشش هنوز سیر نشده باشد گرسنه می‌رود، و بازیکنی که پذیرفته بودش دو آبرو از دست می‌دهد.'],
                ],
                'actions' => [
                    ['name' => 'پذیرفتن کاروان', 'slug' => 'accept_a_caravan', 'type' => RuleActionType::Basic, 'phase' => 'گذاشتن خدمه', 'economy' => 'accept-a-caravan', 'description' => 'کاروانی منتظر را پیش از بسته شدن فصل به یک بخش باز بیاورید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'یک خدمه در دست.', 'value' => '1', 'resource' => 'khadame'],
                        ['type' => RequirementType::Position, 'description' => 'بخشی که در گام فعلی فصل باز باشد.'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'آشپزخانهٔ آن بخش', 'description' => 'کاروانِ پذیرفته‌شده همان چیزی است که آشپزخانه را قابل استفاده می‌کند.'],
                    ]],
                    ['name' => 'کار در آشپزخانه', 'slug' => 'work_the_kitchen', 'type' => RuleActionType::Resource, 'phase' => 'گذاشتن خدمه', 'economy' => 'work-the-kitchen', 'description' => 'برای یک کاروان پذیرفته‌شده آذوقه آماده کنید.', 'requirements' => [
                        ['type' => RequirementType::Ownership, 'description' => 'کاروانی پذیرفته‌شده که هنوز سیر نشده باشد.'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'آذوقه', 'value' => '+2', 'resource' => 'azoughe', 'description' => 'مقدارش با اقتصاد بازی است. اینجا فقط گفته می‌شود که آذوقه می‌رسد.'],
                    ]],
                    ['name' => 'سیر کردن کاروان', 'slug' => 'serve_a_caravan', 'type' => RuleActionType::Basic, 'phase' => 'فیصله', 'economy' => 'serve-a-caravan', 'description' => 'آذوقه را در برابر کاروانی که نگه داشته‌اید تحویل بدهید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'آذوقه‌ای که کاروان می‌خواهد.', 'resource' => 'azoughe'],
                    ], 'effects' => [
                        ['type' => EffectType::Score, 'target' => 'آبرو', 'value' => '+4', 'resource' => 'aberou'],
                        ['type' => EffectType::Discard, 'target' => 'کارت کاروان', 'value' => '1'],
                    ]],
                ],
                'groups' => [
                    ['name' => 'بازی تمام است', 'operator' => LogicOperator::And, 'description' => 'هر دو باید برقرار باشند. تمام شدن فصل وسط دور چیزی را تمام نمی‌کند.', 'conditions' => ['چهار فصل تمام شده است', 'همهٔ خدمه‌ها گذاشته شده‌اند']],
                ],
                'victory' => [
                    ['name' => 'بیشترین آبرو پس از چهار فصل', 'condition' => 'بیشترین آبروی روی میز', 'description' => 'تساوی با کمترین کاروان ناتمام شکسته می‌شود، بعد با بیشترین آذوقه.'],
                ],
                'endings' => [
                    ['name' => 'چهارمین فصل تمام می‌شود', 'condition' => 'چهار فصل تمام شده است', 'description' => 'بازی همیشه تا آخر می‌رود. پایان زودهنگامی وجود ندارد.'],
                ],
                'references' => [
                    ['from' => 'ماندن در راه', 'to' => 'فصل', 'type' => ReferenceType::DependsOn, 'description' => 'ماندن در راه فقط به این دلیل معنا دارد که فصل خودش بخش‌ها را می‌بندد.'],
                    ['from' => 'پذیرایی از یک کاروان', 'to' => 'کاروان‌ها', 'type' => ReferenceType::DependsOn],
                    ['from' => 'خدمه با فصل برمی‌گردد', 'to' => 'هر جا یک خدمه', 'type' => ReferenceType::RelatedTo, 'description' => 'این دو را کنار هم که بخوانید، تمام دلیل تنگ بودن یک دور همین است.'],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'name' => 'قواعد بازنویسی مهمان‌داری',
                'description' => 'قواعدی که در جریان‌اند. کاروان‌ها به جای ردیف مشترک از سه صف جدا می‌آیند، '
                    .'پس پذیرفتن کاروانی که می‌خواهید یعنی نپذیرفتن کاروانی که کس دیگری منتظرش بود — و همین '
                    .'نپذیرفتن، تمام دلیل بازنویسی است.',
                'status' => RuleSetStatus::Active,
                'by' => 'negar@simorgh.test',
                'created' => 50,
                'touched' => 4,
                'mechanics' => [
                    ['name' => 'کارگرگذاری', 'slug' => 'worker_placement', 'category' => MechanicCategory::Action, 'description' => 'سه خدمه برای هر بازیکن، یکی‌یکی روی جاهایی که پشت سرشان بسته می‌شود.'],
                    ['name' => 'درفت کاروان', 'slug' => 'caravan_drafting', 'category' => MechanicCategory::Card, 'description' => 'سه صف رو باز، فقط کارت رویی. برداشتن از یک صف، آن صف را تا آخر دور می‌بندد.'],
                    ['name' => 'گردونهٔ فصل', 'slug' => 'season_wheel', 'category' => MechanicCategory::Progression, 'description' => 'ساعتی مشترک که بخش‌ها را باز و بسته می‌کند.'],
                    ['name' => 'مجموعه‌سازی', 'slug' => 'set_collection', 'category' => MechanicCategory::Scoring, 'description' => 'کاروان‌ها آذوقه را در ترکیب‌های مشخص می‌خواهند.'],
                    ['name' => 'ضربه به حریف', 'slug' => 'take_that', 'category' => MechanicCategory::PlayerInteraction, 'description' => 'چرخاندن زودهنگام فصل، بخش‌ها را روی کس دیگری می‌بندد. کینه‌توزانه، گاهی درست، و پرحرف‌وحدیث‌ترین کنش بازی.'],
                ],
                'phases' => [
                    ['name' => 'برپایی', 'slug' => 'setup', 'type' => GamePhaseType::Setup, 'description' => 'کاروان‌سرا را بچینید، سه صف کاروان بسازید و به هر بازیکن سه خدمه، پنج سکه و دو علوفه بدهید.'],
                    ['name' => 'گردش فصل', 'slug' => 'season_cycle', 'type' => GamePhaseType::Round, 'description' => 'پنج دور، و بعد فصل می‌چرخد و همه‌چیز از نو. چهار گردش یک بازی است.'],
                    ['name' => 'آغاز دور', 'slug' => 'round_start', 'type' => GamePhaseType::Round, 'parent' => 'گردش فصل', 'description' => 'فصل یک گام جلو می‌رود. بخش‌ها با آن باز و بسته می‌شوند.'],
                    ['name' => 'گذاشتن خدمه', 'slug' => 'placement', 'type' => GamePhaseType::Action, 'parent' => 'گردش فصل', 'description' => 'بازیکنان به نوبت هر بار یک خدمه می‌گذارند تا همه گذاشته شوند.'],
                    ['name' => 'فیصله', 'slug' => 'resolution', 'type' => GamePhaseType::Resolution, 'parent' => 'گردش فصل', 'description' => 'خدمه‌های گذاشته‌شده به ترتیب کاروان‌سرا بررسی می‌شوند، نه به ترتیب نوبت.'],
                    ['name' => 'جمع‌وجور', 'slug' => 'cleanup', 'type' => GamePhaseType::Cleanup, 'parent' => 'گردش فصل', 'description' => 'خدمه‌ها برمی‌گردند، اجاره پرداخت می‌شود، آذوقهٔ بالای آستانه فاسد می‌شود و صف‌ها دوباره باز می‌شوند.'],
                    ['name' => 'پایان بازی', 'slug' => 'game_end', 'type' => GamePhaseType::EndGame, 'description' => 'کاروان‌های نگه‌داشته و آبرو شمرده و مقایسه می‌شوند.'],
                ],
                'conditions' => [
                    ['name' => 'همهٔ خدمه‌ها گذاشته شده‌اند', 'type' => ConditionType::GameState, 'operator' => ConditionOperator::IsTrue, 'description' => 'هیچ‌کس خدمه‌ای در دست ندارد.'],
                    ['name' => 'گردش پنج دور طول کشیده است', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '5', 'description' => 'روی گردونه خوانده می‌شود. همان عددی که بحث طول بازی سرش است.'],
                    ['name' => 'چهار گردش تمام شده است', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '4'],
                    ['name' => 'بیشترین آبروی روی میز', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThan, 'value' => '0', 'description' => 'در تساوی، کسی که کاروان‌های ناتمام کمتری دارد جلوتر است.'],
                    ['name' => 'دستهٔ کاروان تمام شده است', 'type' => ConditionType::Card, 'operator' => ConditionOperator::IsTrue, 'description' => 'هر سه صف خالی. یک‌بار در یک بازی پنج‌نفره پیش آمده است.'],
                ],
                'triggers' => [
                    ['name' => 'با چرخش فصل', 'type' => TriggerType::RoundEnd, 'description' => 'پایان دور پنجم یک گردش.'],
                    ['name' => 'وقتی از یک صف برداشته می‌شود', 'type' => TriggerType::ActionExecuted, 'description' => 'آن صف را تا آخر دور می‌بندد. همان نپذیرفتنی که بازنویسی برایش انجام شد.'],
                ],
                'transitions' => [
                    ['from' => 'برپایی', 'to' => 'آغاز دور'],
                    ['from' => 'آغاز دور', 'to' => 'گذاشتن خدمه'],
                    ['from' => 'گذاشتن خدمه', 'to' => 'فیصله', 'condition' => 'همهٔ خدمه‌ها گذاشته شده‌اند'],
                    ['from' => 'فیصله', 'to' => 'جمع‌وجور'],
                    ['from' => 'جمع‌وجور', 'to' => 'آغاز دور'],
                    ['from' => 'جمع‌وجور', 'to' => 'پایان بازی', 'condition' => 'چهار گردش تمام شده است', 'trigger' => 'با چرخش فصل'],
                ],
                'rules' => [
                    ['name' => 'گذاشتن خدمه', 'slug' => 'placing_crew', 'type' => RuleType::Turn, 'phase' => 'گذاشتن خدمه', 'description' => 'از بازیکن اول و در جهت عقربه‌های ساعت، هر بازیکن یک خدمه روی یک جای باز می‌گذارد.'],
                    ['name' => 'هر جا یک خدمه', 'slug' => 'one_crew_per_space', 'parent' => 'گذاشتن خدمه', 'type' => RuleType::Turn, 'phase' => 'گذاشتن خدمه', 'description' => 'هر جا یک خدمه می‌گیرد و تا آخر دور بسته می‌ماند.'],
                    ['name' => 'خدمه با فصل برمی‌گردد', 'slug' => 'crew_returns', 'parent' => 'گذاشتن خدمه', 'type' => RuleType::Turn, 'phase' => 'جمع‌وجور', 'description' => 'هر خدمه با چرخش فصل برمی‌گردد. خدمه‌ای که خرج نشود هدر رفته است.'],
                    ['name' => 'کاروان‌ها', 'slug' => 'caravans', 'type' => RuleType::Resource, 'description' => 'سه صف، رو باز، فقط کارت رویی. صفی که این دور از آن برداشته شود تا دور بعد بسته است.'],
                    ['name' => 'برداشتن از یک صف آن را می‌بندد', 'slug' => 'taking_closes_a_queue', 'parent' => 'کاروان‌ها', 'type' => RuleType::PlayerInteraction, 'phase' => 'گذاشتن خدمه', 'description' => 'تمام نکتهٔ بازنویسی: کارتی که برمی‌دارید همان کارتی است که کس دیگری داشت می‌خواندش، و صف پشتش هم با آن می‌رود.'],
                    ['name' => 'پذیرایی از یک کاروان', 'slug' => 'serving_a_caravan', 'parent' => 'کاروان‌ها', 'type' => RuleType::Scoring, 'phase' => 'فیصله', 'description' => 'آذوقه‌ای را که کاروان می‌خواهد تحویل بدهید تا آبرویش را بگیرید.'],
                    ['name' => 'شش کاروان سقف است', 'slug' => 'six_caravans_limit', 'parent' => 'کاروان‌ها', 'type' => RuleType::Resource, 'description' => 'انتخاب شده چون روی صفحهٔ بازیکن جا می‌شود، که کارگاه می‌داند دلیل طراحی نیست.'],
                    ['name' => 'فصل', 'slug' => 'the_season', 'type' => RuleType::General, 'description' => 'فصل هر دور یک گام جلو می‌رود و هرگز برنمی‌گردد.'],
                    ['name' => 'ماندن در راه', 'slug' => 'stranded', 'parent' => 'فصل', 'type' => RuleType::General, 'phase' => 'جمع‌وجور', 'description' => 'کاروانی که هنگام بسته شدن بخشش سیر نشده باشد گرسنه می‌رود و دو آبرو می‌برد.'],
                    ['name' => 'چرخاندن زودهنگام فصل', 'slug' => 'turning_early', 'parent' => 'فصل', 'type' => RuleType::PlayerInteraction, 'phase' => 'گذاشتن خدمه', 'description' => 'یک خدمه می‌تواند فصل را زودتر از موعد یک گام جلو ببرد و بخش‌ها را روی هر کس که هنوز یکی را نگه داشته ببندد.'],
                    ['name' => 'فساد آذوقه', 'slug' => 'spoilage', 'type' => RuleType::Resource, 'phase' => 'جمع‌وجور', 'description' => 'آذوقهٔ بالای آستانه در پایان گردش از بین می‌رود. هیچ جلسهٔ ثبت‌شده‌ای به آن نرسیده است.'],
                    ['name' => 'برپا کردن', 'slug' => 'setting_up', 'type' => RuleType::Setup, 'phase' => 'برپایی', 'description' => 'شش بخش، سه صف کاروان که جدا بر زده می‌شوند، سه خدمه، پنج سکه و دو علوفه برای هر نفر. فصل از گام صفر شروع می‌شود.'],
                    ['name' => 'بازار مزدور', 'slug' => 'hand_market', 'type' => RuleType::Resource, 'status' => RuleStatus::Deprecated, 'description' => 'برای سابقه نگه داشته شده. دو بار برداشته و دوباره گذاشته شد بدون اثر قابل اندازه‌گیری بر امتیاز، طول بازی یا تصمیم‌هایی که بازیکنان بعد از بازی توصیف می‌کنند.'],
                ],
                'actions' => [
                    ['name' => 'پذیرفتن کاروان', 'slug' => 'accept_a_caravan', 'type' => RuleActionType::Basic, 'phase' => 'گذاشتن خدمه', 'economy' => 'accept-a-caravan', 'description' => 'کاروانی منتظر را پیش از بسته شدن فصل به یک بخش باز بیاورید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'یک خدمه در دست.', 'value' => '1', 'resource' => 'khadame'],
                        ['type' => RequirementType::Position, 'description' => 'بخشی که در گام فعلی فصل باز باشد.'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'آشپزخانهٔ آن بخش'],
                    ]],
                    ['name' => 'کار در آشپزخانه', 'slug' => 'work_the_kitchen', 'type' => RuleActionType::Resource, 'phase' => 'گذاشتن خدمه', 'economy' => 'work-the-kitchen', 'description' => 'برای یک کاروان پذیرفته‌شده آذوقه آماده کنید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'یک خدمه در دست.', 'value' => '1', 'resource' => 'khadame'],
                        ['type' => RequirementType::Ownership, 'description' => 'کاروانی پذیرفته‌شده که هنوز سیر نشده باشد.'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'آذوقه', 'value' => '+2', 'resource' => 'azoughe', 'description' => 'چند تا، با اقتصاد بازی است. اینجا فقط گفته می‌شود که آذوقه می‌رسد.'],
                    ]],
                    ['name' => 'باز کردن یک اتاق', 'slug' => 'open_a_room', 'type' => RuleActionType::Build, 'phase' => 'گذاشتن خدمه', 'economy' => 'open-a-room', 'description' => 'یک اتاق تازه باز کنید تا تا آخر بازی اجاره بدهد.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'سکه و یک علوفه.', 'resource' => 'sekke'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'یک بخش تازه'],
                    ]],
                    ['name' => 'سیر کردن کاروان', 'slug' => 'serve_a_caravan', 'type' => RuleActionType::Basic, 'phase' => 'فیصله', 'economy' => 'serve-a-caravan', 'description' => 'آذوقه را در برابر کاروانی که نگه داشته‌اید تحویل بدهید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'آذوقه‌ای که کاروان می‌خواهد.', 'resource' => 'azoughe'],
                    ], 'effects' => [
                        ['type' => EffectType::Score, 'target' => 'آبرو', 'value' => '+4', 'resource' => 'aberou'],
                        ['type' => EffectType::Discard, 'target' => 'کارت کاروان', 'value' => '1'],
                    ]],
                    ['name' => 'گرفتن مزدور', 'slug' => 'hire_a_hand', 'type' => RuleActionType::Build, 'phase' => 'گذاشتن خدمه', 'economy' => 'hire-a-hand', 'status' => RuleStatus::Deprecated, 'description' => 'یک جفت دست چهارم تا آخر بازی. برای سابقه نگه داشته شده تا کارگاه تصمیم بگیرد بازار می‌ماند یا نه.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'سکه و یک علوفه.', 'resource' => 'sekke'],
                    ], 'effects' => [
                        ['type' => EffectType::Unlock, 'target' => 'خدمهٔ چهارم در هر گردش'],
                    ]],
                    ['name' => 'چرخاندن فصل', 'slug' => 'turn_the_season', 'type' => RuleActionType::Special, 'phase' => 'گذاشتن خدمه', 'economy' => 'turn-the-season', 'description' => 'فصل را زودتر از موعد یک گام جلو ببرید و بخش‌ها را پیش از وقتشان ببندید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'یک خدمه در دست.', 'value' => '1', 'resource' => 'khadame'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'گام فصل', 'value' => '+1', 'resource' => 'season-step'],
                        ['type' => EffectType::Lock, 'target' => 'بخش‌های سمت رو به پایانِ فصل'],
                    ]],
                    ['name' => 'رد کردن نوبت', 'slug' => 'pass', 'type' => RuleActionType::Pass, 'phase' => 'گذاشتن خدمه', 'description' => 'نگذاشتن خدمه و نگه داشتنش. مجاز، گاهی درست، و تقریباً هرگز انجام‌نشده.', 'effects' => [
                        ['type' => EffectType::TurnChange, 'target' => 'بازیکن بعدی'],
                    ]],
                ],
                'groups' => [
                    ['name' => 'بازی تمام است', 'operator' => LogicOperator::Or, 'description' => 'هر کدام تمامش می‌کند. خالی شدن دسته یک‌بار در یک بازی پنج‌نفره پیش آمده است.', 'conditions' => ['چهار گردش تمام شده است', 'دستهٔ کاروان تمام شده است']],
                ],
                'victory' => [
                    ['name' => 'بیشترین آبرو در پایان بازی', 'condition' => 'بیشترین آبروی روی میز', 'description' => 'تساوی با کمترین کاروان ناتمام شکسته می‌شود، بعد با بیشترین آذوقه.'],
                ],
                'endings' => [
                    ['name' => 'چهار گردش فصل تمام می‌شود', 'condition' => 'چهار گردش تمام شده است', 'description' => 'پایان معمول، و همانی که بحث طول بازی می‌خواهد به سه برساندش.'],
                    ['name' => 'دستهٔ کاروان تمام می‌شود', 'condition' => 'دستهٔ کاروان تمام شده است', 'description' => 'گردش جاری را تمام کنید و بعد بشمارید. بعد از اینکه در یک بازی پنج‌نفره پیش آمد و کسی نمی‌دانست چه کند نوشته شد.'],
                ],
                'references' => [
                    ['from' => 'ماندن در راه', 'to' => 'فصل', 'type' => ReferenceType::DependsOn],
                    ['from' => 'چرخاندن زودهنگام فصل', 'to' => 'فصل', 'type' => ReferenceType::Modifies, 'description' => 'تنها چیزی در بازی که ساعت را از برنامهٔ خودش بیرون می‌برد.'],
                    ['from' => 'برداشتن از یک صف آن را می‌بندد', 'to' => 'کاروان‌ها', 'type' => ReferenceType::Modifies],
                    ['from' => 'پذیرایی از یک کاروان', 'to' => 'کاروان‌ها', 'type' => ReferenceType::DependsOn],
                    ['from' => 'شش کاروان سقف است', 'to' => 'کاروان‌ها', 'type' => ReferenceType::ExceptionTo, 'description' => 'هر صفی، هر وقتی — مگر وقتی صفحهٔ بازیکن پر باشد.'],
                    ['from' => 'فساد آذوقه', 'to' => 'پذیرایی از یک کاروان', 'type' => ReferenceType::RelatedTo, 'description' => 'کنار هم قرار است انبار کردن آذوقه را کار بدی کنند. در عمل کسی آن‌قدر انبار نکرده که معلوم شود.'],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'karvansara',
                'version' => 3,
                'cloneOf' => 'قواعد بازنویسی مهمان‌داری',
                'name' => 'پیش‌نویس سه‌گردشی',
                'description' => 'پیشنهاد بحث طول بازی، رونوشت‌شده از قواعد در جریان چون راه دیگری برای عوض '
                    .'کردن قواعدی که در جریان‌اند وجود ندارد. سه گردش به جای چهار، با پاداش‌های پایان بازی '
                    .'بالاتر تا جای دورهای ازدست‌رفته را بگیرد.',
                'by' => 'arash@simorgh.test',
                'created' => 14,
                'touched' => 3,
                'edits' => [
                    'conditions' => [
                        'چهار گردش تمام شده است' => ['name' => 'سه گردش تمام شده است', 'value' => '3', 'description' => 'تغییری که تمام پیش‌نویس برای آن است.'],
                    ],
                    'rules' => [
                        ['name' => 'پاداش‌های پایان بازی بیشتر می‌ارزند', 'slug' => 'bigger_end_bonuses', 'type' => RuleType::Scoring, 'phase' => 'پایان بازی', 'description' => 'هر گونه کاروانِ سیرشده در پایان یک امتیاز بیشتر از حالت چهار گردشی می‌ارزد. نوشته شده تا دامنهٔ امتیاز ثابت بماند در حالی که بازی کوتاه‌تر می‌شود؛ کسی هنوز بررسی نکرده که می‌ماند یا نه.'],
                    ],
                    'rename' => [
                        'چهار گردش فصل تمام می‌شود' => 'سه گردش فصل تمام می‌شود',
                    ],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::SIMORGH,
                'game' => 'qanat',
                'version' => 2,
                'name' => 'قواعد کاریز جداگانه',
                'description' => 'هنوز به‌زحمت یک نظام قواعد است. دو کنش، آبی که هر وقت کسی بگوید جاری '
                    .'می‌شود، و هیچ پایانی — که دقیقاً شکل یک مجموعه‌قاعده در روزی است که کسی شروعش می‌کند.',
                'status' => RuleSetStatus::Draft,
                'by' => 'arash@simorgh.test',
                'created' => 29,
                'touched' => 8,
                'mechanics' => [
                    ['name' => 'شانس‌آزمایی', 'slug' => 'push_your_luck', 'category' => MechanicCategory::Dice, 'description' => 'هر گام حفر، کاریز را عمیق‌تر می‌کند. یکی بیشتر همیشه می‌ارزد، درست تا وقتی که دیگر نمی‌ارزد.'],
                ],
                'phases' => [
                    ['name' => 'برپایی', 'slug' => 'setup', 'type' => GamePhaseType::Setup, 'description' => 'شش کاشی حفر برای هر نفر، دو کاریز، چهار گام در هر کدام.'],
                    ['name' => 'حفر', 'slug' => 'digging', 'type' => GamePhaseType::Action, 'description' => 'بازیکنان به نوبت در هر کدام از دو کاریز گام می‌زنند.'],
                    ['name' => 'جاری شدن آب', 'slug' => 'flowing', 'type' => GamePhaseType::Resolution, 'description' => 'هر کس آب را باز کرده باشد کاریز را می‌گشاید. هر چه داخلش هست یا امتیاز می‌شود یا از دست می‌رود.'],
                ],
                /*
                 * Written and then never pointed at, which is the shape the
                 * validator reports as an unused condition.
                 */
                'conditions' => [
                    ['name' => 'کاریز از عمق ریزش گذشته است', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::GreaterThan, 'value' => '7', 'description' => 'در جلسهٔ کاریز جداگانه طرحش زده شد و هنوز به چیزی وصل نیست.'],
                ],
                'triggers' => [
                    ['name' => 'وقتی کسی آب را باز می‌کند', 'type' => TriggerType::PlayerEvent, 'description' => 'تنها چیزی که یک دور را تمام می‌کند. به هیچ گذاری وصل نیست، چون هنوز جایی برای رفتن بازی وجود ندارد.'],
                ],
                'transitions' => [
                    ['from' => 'برپایی', 'to' => 'حفر'],
                    ['from' => 'حفر', 'to' => 'جاری شدن آب'],
                ],
                'rules' => [
                    ['name' => 'زدن یک گام', 'slug' => 'digging_a_step', 'type' => RuleType::Action, 'phase' => 'حفر', 'description' => 'یک کاشی حفر را در جای باز هر کدام از دو کاریز بگذارید. برنمی‌گردد.'],
                    ['name' => 'سرک کشیدن در کاریز دیگری', 'slug' => 'looking_in_the_other', 'type' => RuleType::PlayerInteraction, 'phase' => 'حفر', 'description' => 'هر وقت بخواهید می‌توانید گام‌های کاریز حریف را بشمارید. همین است که تصمیم زمان‌بندی را از حساب‌وکتاب به خواندن حریف تبدیل می‌کند.'],
                    ['name' => 'عمق', 'slug' => 'depth', 'type' => RuleType::Resource, 'description' => null],
                    ['name' => 'باز کردن آب', 'slug' => 'calling_the_water', 'type' => RuleType::Action, 'phase' => 'جاری شدن آب', 'description' => 'هر بازیکنی می‌تواند به جای حفر، آب را باز کند. هر دو کاریز با هم فیصله می‌یابند.'],
                ],
                'actions' => [
                    ['name' => 'زدن یک گام', 'slug' => 'dig_a_step', 'type' => RuleActionType::Resource, 'phase' => 'حفر', 'economy' => 'dig-a-step', 'description' => 'یک کاشی حفر در یک جای باز بگذارید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'کاشی حفری که هنوز در انبارتان مانده باشد.', 'resource' => 'digger'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'عمق', 'value' => '+1', 'resource' => 'depth', 'description' => 'هر گام، کاریز را یک قدم به ریزش نزدیک‌تر می‌کند.'],
                    ]],
                    /*
                     * No phase — an error rather than a warning, and the reason
                     * this rule set cannot be activated. Which is the point of
                     * seeding it.
                     */
                    ['name' => 'باز کردن آب', 'slug' => 'open_the_water', 'type' => RuleActionType::Special, 'economy' => 'open-the-water', 'description' => 'کاریزها را باز کنید و هر چه دوام آورده را بشمارید. هنوز کسی تصمیم نگرفته این یک گذاشتن است یا کاری که هر وقت بخواهید می‌کنید.', 'effects' => [
                        ['type' => EffectType::EndGame, 'target' => 'دور'],
                    ]],
                ],
                'endings' => [
                    ['name' => 'کاشی‌های حفر تمام می‌شوند', 'description' => 'احتمالاً. کسی شرطی برایش ننوشته، و دو جلسه با توافق تمام شدند نه با قاعده.'],
                ],
                'references' => [
                    ['from' => 'باز کردن آب', 'to' => 'عمق', 'type' => ReferenceType::DependsOn, 'description' => 'تمام تصمیم، خواندن عمق است — و قاعدهٔ عمق سرفصلی است که چیزی زیرش نیست.'],
                ],
            ],
            [
                'workspace' => SampleFaStudioSeeder::OTAGH,
                'game' => 'chai-o-khorma',
                'version' => 3,
                'name' => 'قواعد نسخهٔ کتابچه',
                'description' => 'همان قواعدی که کتابچهٔ نوشته‌شده کلمه‌به‌کلمه توصیفشان می‌کند. از این‌جا به '
                    .'بعد هیچ چیزی بدون کتابچهٔ تازه عوض نمی‌شود، و برای همین هر پلی‌تست بعدی با همین‌ها سنجیده '
                    .'می‌شود.',
                'status' => RuleSetStatus::Active,
                'by' => 'mahsa@otagh.test',
                'created' => 46,
                'touched' => 18,
                'mechanics' => [
                    ['name' => 'دست‌گیری', 'slug' => 'trick_taking', 'category' => MechanicCategory::Card, 'description' => 'چهار خال، اگر داری همان خال را بریز، بالاترینِ خالِ شروع دست را می‌برد.'],
                    ['name' => 'پیشنهاد', 'slug' => 'bidding', 'category' => MechanicCategory::PlayerInteraction, 'description' => 'یک پیشنهاد پیش از دست، و فقط پیشنهاد دقیق امتیاز می‌آورد.'],
                    ['name' => 'استکان داغ', 'slug' => 'hot_potato', 'category' => MechanicCategory::PlayerInteraction, 'description' => 'استکان به آخرین برندهٔ دست می‌رسد و هر دست یک امتیاز از دارنده‌اش می‌گیرد.'],
                ],
                'phases' => [
                    ['name' => 'برپایی', 'slug' => 'setup', 'type' => GamePhaseType::Setup, 'description' => 'بر بزنید، دوازده کارت به هر نفر بدهید، و استکان را به کسی که پخش کرده بدهید.'],
                    ['name' => 'دست', 'slug' => 'hand', 'type' => GamePhaseType::Round, 'description' => 'یک پخش، تا آخر بازی‌شده و شمرده‌شده. دست‌ها ادامه می‌یابند تا کسی به چهارده برسد.'],
                    ['name' => 'پیشنهاد دادن', 'slug' => 'bidding', 'type' => GamePhaseType::Action, 'parent' => 'دست', 'description' => 'هر بازیکن می‌گوید قصد دارد چند دست ببرد.'],
                    ['name' => 'دست‌ها', 'slug' => 'tricks', 'type' => GamePhaseType::Turn, 'parent' => 'دست', 'description' => 'دوازده دست، هر کدام را برندهٔ قبلی شروع می‌کند.'],
                    ['name' => 'شمارش', 'slug' => 'scoring', 'type' => GamePhaseType::Resolution, 'parent' => 'دست', 'description' => 'دست‌های برده را با پیشنهاد بسنجید و بعد استکان را رد کنید.'],
                    ['name' => 'پایان بازی', 'slug' => 'game_end', 'type' => GamePhaseType::EndGame, 'description' => 'دستی که کسی در آن از چهارده می‌گذرد تا آخر بازی می‌شود، بعد برنده معلوم می‌شود.'],
                ],
                'conditions' => [
                    ['name' => 'همه پیشنهاد داده‌اند', 'type' => ConditionType::GameState, 'operator' => ConditionOperator::IsTrue],
                    ['name' => 'دوازده دست بازی شده است', 'type' => ConditionType::Counter, 'operator' => ConditionOperator::Equals, 'value' => '12'],
                    ['name' => 'کسی چهارده امتیاز دارد', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThanOrEqual, 'value' => '14', 'description' => 'فقط در پایان یک دست بررسی می‌شود، هرگز وسط دست.'],
                    ['name' => 'بیشترین امتیاز در پایان دست', 'type' => ConditionType::Score, 'operator' => ConditionOperator::GreaterThan, 'value' => '0', 'description' => 'تساوی مشترک است. کتابچه در یک خط گفته و کسی بحثی نکرده.'],
                ],
                'triggers' => [
                    ['name' => 'در پایان هر دست', 'type' => TriggerType::RoundEnd, 'description' => 'شمارش، جریمهٔ استکان و رد کردن استکان همه به همین یک لحظه وصل‌اند.'],
                    ['name' => 'وقتی آخرین دست برده می‌شود', 'type' => TriggerType::PlayerEvent, 'description' => 'هر کس ببردش، استکان را برای دست بعد نگه می‌دارد.'],
                ],
                'transitions' => [
                    ['from' => 'برپایی', 'to' => 'پیشنهاد دادن'],
                    ['from' => 'پیشنهاد دادن', 'to' => 'دست‌ها', 'condition' => 'همه پیشنهاد داده‌اند'],
                    ['from' => 'دست‌ها', 'to' => 'شمارش', 'condition' => 'دوازده دست بازی شده است', 'trigger' => 'وقتی آخرین دست برده می‌شود'],
                    ['from' => 'شمارش', 'to' => 'پایان بازی', 'condition' => 'کسی چهارده امتیاز دارد', 'trigger' => 'در پایان هر دست'],
                    ['from' => 'شمارش', 'to' => 'پیشنهاد دادن', 'trigger' => 'در پایان هر دست'],
                ],
                'rules' => [
                    ['name' => 'پخش کردن', 'slug' => 'dealing', 'type' => RuleType::Setup, 'phase' => 'برپایی', 'description' => 'دوازده کارت به هر نفر از دسته‌ای چهل‌وهشت‌تایی. پخش‌کننده استکان را می‌گیرد و نوبت پخش هر دست یکی به چپ می‌رود.'],
                    ['name' => 'پیشنهاد', 'slug' => 'the_bid', 'type' => RuleType::Turn, 'phase' => 'پیشنهاد دادن', 'description' => 'از سمت چپ پخش‌کننده، هر بازیکن عددی از صفر تا دوازده می‌گوید. پیشنهادها علنی‌اند و برای جمعشان تنظیم نمی‌شوند.'],
                    ['name' => 'عوض کردن پیشنهاد', 'slug' => 'changing_a_bid', 'parent' => 'پیشنهاد', 'type' => RuleType::Special, 'phase' => 'پیشنهاد دادن', 'description' => 'بازیکن می‌تواند با خرج کردن تعارف، پیشنهاد خودش را به ازای هر تعارف یک پله جابه‌جا کند، بعد از شنیدن پیشنهاد کس دیگری. تعارف کاربرد دیگری ندارد.'],
                    ['name' => 'خال را دنبال کنید', 'slug' => 'following_suit', 'type' => RuleType::Turn, 'phase' => 'دست‌ها', 'description' => 'اگر خال شروع را داری همان را بریز. اگر نداری هر چه خواستی؛ خال حکمی وجود ندارد.'],
                    ['name' => 'بردن یک دست', 'slug' => 'taking_a_trick', 'parent' => 'خال را دنبال کنید', 'type' => RuleType::Turn, 'phase' => 'دست‌ها', 'description' => 'بالاترین کارتِ خالِ شروع دست را می‌برد، و برنده دست بعد را شروع می‌کند.'],
                    ['name' => 'شمردن یک دست', 'slug' => 'scoring_a_hand', 'type' => RuleType::Scoring, 'phase' => 'شمارش', 'description' => 'پیشنهادی که دقیق درآید سه امتیاز دارد. پیشنهادی که از هر طرف نخورد هیچ امتیازی ندارد — بیشتر بردن هم بهتر از کمتر بردن نیست.'],
                    ['name' => 'استکان', 'slug' => 'the_ember', 'type' => RuleType::PlayerInteraction, 'description' => 'در هر لحظه دقیقاً یک بازیکن استکان را دارد. در پایان هر دست یک امتیاز از او می‌گیرد و در آغاز دست بعد یک تعارف اضافه به او می‌دهد.'],
                    ['name' => 'رد کردن استکان', 'slug' => 'passing_the_ember', 'parent' => 'استکان', 'type' => RuleType::PlayerInteraction, 'phase' => 'شمارش', 'description' => 'هر کس آخرین دستِ دست را ببرد، استکان را هم با آن می‌برد.'],
                    ['name' => 'تمام کردن دست', 'slug' => 'finishing_the_hand', 'type' => RuleType::EndGame, 'phase' => 'پایان بازی', 'description' => 'دستی که کسی در آن از چهارده می‌گذرد تا آخر بازی می‌شود. دو بار دو نفر در یک دست از چهارده گذشته‌اند و هر دو بار مجموع بالاتر برده است.'],
                ],
                'actions' => [
                    ['name' => 'پیشنهاد دادن', 'slug' => 'bid', 'type' => RuleActionType::Basic, 'phase' => 'پیشنهاد دادن', 'economy' => 'bid', 'description' => 'بگویید قصد دارید چند دست ببرید.', 'requirements' => [
                        ['type' => RequirementType::Turn, 'description' => 'نوبت شما برای پیشنهاد، از چپ پخش‌کننده در جهت عقربه‌های ساعت.'],
                    ], 'effects' => [
                        ['type' => EffectType::StateChange, 'target' => 'پیشنهاد شما، تا آخر دست'],
                    ]],
                    ['name' => 'خرج کردن تعارف روی پیشنهاد', 'slug' => 'spend_favour', 'type' => RuleActionType::Free, 'phase' => 'پیشنهاد دادن', 'economy' => 'spend-favour', 'description' => 'پیشنهاد خودتان را به ازای هر تعارف یک پله جابه‌جا کنید.', 'requirements' => [
                        ['type' => RequirementType::Resource, 'description' => 'تعارف در دست.', 'value' => '1', 'resource' => 'favour'],
                        ['type' => RequirementType::GameState, 'description' => 'دست‌کم یک نفر دیگر پیشنهاد داده باشد.'],
                    ], 'effects' => [
                        ['type' => EffectType::Resource, 'target' => 'تعارف', 'value' => '-1', 'resource' => 'favour'],
                        ['type' => EffectType::StateChange, 'target' => 'پیشنهاد شما'],
                    ]],
                    ['name' => 'ریختن یک کارت', 'slug' => 'play_a_card', 'type' => RuleActionType::Card, 'phase' => 'دست‌ها', 'economy' => 'take-a-trick', 'description' => 'یک کارت در دست بریزید، و اگر خال شروع را دارید همان را.', 'requirements' => [
                        ['type' => RequirementType::Turn, 'description' => 'نوبت شما در این دست.'],
                        ['type' => RequirementType::Card, 'description' => 'کارتی از خال شروع، اگر داشته باشید.'],
                    ], 'effects' => [
                        ['type' => EffectType::Discard, 'target' => 'کارت ریخته‌شده', 'value' => '1'],
                    ]],
                    ['name' => 'شمردن دست', 'slug' => 'score_the_hand', 'type' => RuleActionType::Basic, 'phase' => 'شمارش', 'economy' => 'score-the-hand', 'description' => 'دست‌های برده را با پیشنهاد بسنجید و بپردازید.', 'requirements' => [
                        ['type' => RequirementType::GameState, 'description' => 'هر دوازده دست بازی شده باشد.', 'value' => '12'],
                    ], 'effects' => [
                        ['type' => EffectType::Score, 'target' => 'امتیاز', 'value' => '+3', 'resource' => 'point', 'description' => 'فقط برای پیشنهادی که دقیق درآمده. بیشتر و کمتر هر دو هیچ.'],
                        ['type' => EffectType::Damage, 'target' => 'امتیاز دارندهٔ استکان', 'value' => '-1', 'resource' => 'point'],
                        ['type' => EffectType::PhaseChange, 'target' => 'دست بعد، یا پایان بازی'],
                    ]],
                ],
                'groups' => [
                    ['name' => 'دست تمام است', 'operator' => LogicOperator::And, 'conditions' => ['همه پیشنهاد داده‌اند', 'دوازده دست بازی شده است']],
                ],
                'victory' => [
                    ['name' => 'بیشترین امتیاز وقتی کسی از چهارده می‌گذرد', 'condition' => 'بیشترین امتیاز در پایان دست', 'description' => 'در تساوی مشترک است. کتابچه در یک خط گفته و کسی بحثی نکرده.'],
                ],
                'endings' => [
                    ['name' => 'بازیکنی به چهارده امتیاز می‌رسد', 'condition' => 'کسی چهارده امتیاز دارد', 'description' => 'در پایان یک دست بررسی می‌شود. دست همیشه اول تا آخر بازی می‌شود.'],
                ],
                'references' => [
                    ['from' => 'عوض کردن پیشنهاد', 'to' => 'پیشنهاد', 'type' => ReferenceType::Modifies, 'description' => 'تنها راهی که پیشنهاد پس از گفته شدن جابه‌جا می‌شود.'],
                    ['from' => 'رد کردن استکان', 'to' => 'بردن یک دست', 'type' => ReferenceType::DependsOn, 'description' => 'و برای همین آخرین دستِ هر دست کاملاً جور دیگری بازی می‌شود.'],
                    ['from' => 'شمردن یک دست', 'to' => 'پیشنهاد', 'type' => ReferenceType::DependsOn],
                    ['from' => 'تمام کردن دست', 'to' => 'شمردن یک دست', 'type' => ReferenceType::DependsOn],
                    ['from' => 'استکان', 'to' => 'عوض کردن پیشنهاد', 'type' => ReferenceType::RelatedTo, 'description' => 'استکان یک امتیاز می‌گیرد و یک تعارف می‌دهد، و تعارف فقط پیشنهاد را جابه‌جا می‌کند. جدا از هم، هیچ‌کدام چیز مهمی به نظر نمی‌رسند.'],
                ],
            ],
        ];
    }
}
