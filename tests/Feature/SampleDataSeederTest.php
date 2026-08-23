<?php

use Database\Seeders\DesignFrameworkSeeder;
use Database\Seeders\MechanicSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\SampleStudioSeeder;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Analysis\RuleSetValidator;
use Modules\GameRules\Infrastructure\GameEconomy\EconomyDirectory;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\Workspace\Domain\Models\Workspace;

/*
|--------------------------------------------------------------------------
| The worked example
|--------------------------------------------------------------------------
|
| The sample data's whole value is that its records refer to each other: an
| iteration cites a playtest, the playtest was run against a design version,
| and the balance profile the observations belong to hangs off that same
| version. A citation that resolves to nothing looks identical on screen to one
| that resolves, which is why these are tests rather than a manual check.
|
| Re-running is covered too. The seeder is keyed by address throughout so that
| whoever maintains the studio can correct a typo and seed again, and that only
| stays true if somebody notices when it stops being true.
|
*/

beforeEach(function () {
    $this->seed(MechanicSeeder::class);
    $this->seed(DesignFrameworkSeeder::class);
});

it('seeds two workspaces whose games are addressed within them', function () {
    $this->seed(SampleDataSeeder::class);

    expect(Workspace::query()->count())->toBe(2);

    $lantern = Workspace::query()->where('slug', SampleStudioSeeder::LANTERN)->firstOrFail();

    expect($lantern->members()->count())->toBe(4)
        ->and($lantern->ownerMembership()?->user->email)->toBe('mara@lanternandanvil.test')
        ->and(Game::query()->where('workspace_id', $lantern->getKey())->count())->toBe(4);
});

it('gives one person a different role in each workspace', function () {
    $this->seed(SampleDataSeeder::class);

    $mara = User::query()->where('email', 'mara@lanternandanvil.test')->firstOrFail();

    $roles = Workspace::query()
        ->get()
        ->mapWithKeys(fn (Workspace $workspace) => [
            $workspace->slug => $workspace->memberFor($mara)?->role->value,
        ]);

    expect($roles[SampleStudioSeeder::LANTERN])->toBe('owner')
        ->and($roles[SampleStudioSeeder::NIGHTSHIFT])->toBe('member');
});

it('never evaluates a criterion the design record already answers', function () {
    $this->seed(SampleDataSeeder::class);

    $answeredElsewhere = GameFramework::query()
        ->with('criterionEvaluations.criterion')
        ->get()
        ->flatMap->criterionEvaluations
        ->filter(fn ($evaluation) => $evaluation->criterion->isAnsweredByTheDesignRecord());

    expect($answeredElsewhere)->toBeEmpty();
});

it('runs every playtest against a version of its own game', function () {
    $this->seed(SampleDataSeeder::class);

    $strays = Playtest::query()
        ->with('version')
        ->get()
        ->reject(fn (Playtest $playtest) => $playtest->version->game_id === $playtest->game_id);

    expect(Playtest::query()->count())->toBeGreaterThan(0)
        ->and($strays)->toBeEmpty();
});

it('cites evidence that resolves to a real record', function () {
    $this->seed(SampleDataSeeder::class);

    $cited = DecisionEvidence::query()
        ->where('type', '!=', EvidenceType::Note)
        ->get();

    expect($cited)->not->toBeEmpty()
        ->and($cited->whereNull('reference_id'))->toBeEmpty();

    $playtestIds = Playtest::query()->pluck('id');

    $citedPlaytests = $cited->where('type', EvidenceType::Playtest)->pluck('reference_id');

    expect($citedPlaytests)->not->toBeEmpty()
        ->and($citedPlaytests->diff($playtestIds))->toBeEmpty();
});

it('builds every iteration on a prototype version of the same game', function () {
    $this->seed(SampleDataSeeder::class);

    $strays = Iteration::query()
        ->with(['version', 'prototypeVersion.prototype'])
        ->get()
        ->reject(fn (Iteration $iteration) => $iteration->version->game_id === $iteration->game_id
            && $iteration->prototypeVersion->prototype->game_id === $iteration->game_id);

    expect(Iteration::query()->count())->toBeGreaterThan(0)
        ->and($strays)->toBeEmpty();
});

it('keeps a balance profile for each design version that has one', function () {
    $this->seed(SampleDataSeeder::class);

    $harbourmaster = Game::query()->where('slug', 'harbourmaster')->firstOrFail();

    $profiles = BalanceProfile::query()
        ->whereIn('game_version_id', $harbourmaster->versions()->pluck('id'))
        ->get();

    expect($profiles)->toHaveCount(2)
        ->and($profiles->where('status', 'active'))->toHaveCount(1);
});

it('captures snapshots in the shape the comparison screen reads', function () {
    $this->seed(SampleDataSeeder::class);

    $snapshots = BalanceSnapshot::query()->get();

    expect($snapshots)->not->toBeEmpty();

    foreach ($snapshots as $snapshot) {
        expect($snapshot->snapshot_data)->toHaveKeys(['version', 'profile', 'resources', 'flows', 'actions', 'variables'])
            ->and($snapshot->tally()['resources'])->toBeGreaterThan(0);
    }
});

it('keeps a rule set for each design version that has one', function () {
    $this->seed(SampleDataSeeder::class);

    $harbourmaster = Game::query()->where('slug', 'harbourmaster')->firstOrFail();

    $sets = RuleSet::query()
        ->whereIn('game_version_id', $harbourmaster->versions()->pluck('id'))
        ->get();

    /* The v2 rules archived, the v3 rules in play, and the v3 draft cloned from them. */
    expect($sets)->toHaveCount(3)
        ->and($sets->where('status', 'active'))->toHaveCount(1)
        ->and($sets->where('status', 'archived'))->toHaveCount(1);
});

it('clones the rules in play rather than editing them', function () {
    $this->seed(SampleDataSeeder::class);

    $live = RuleSet::query()->where('name', 'Contract rework rules')->firstOrFail();
    $draft = RuleSet::query()->where('name', 'Three-cycle draft')->firstOrFail();

    expect($draft->cloned_from_rule_set_id)->toBe($live->id)
        ->and($draft->game_version_id)->toBe($live->game_version_id)
        ->and($draft->status->value)->toBe('draft');

    /*
     * The guarantee cloning exists for. Nothing in the copy is a row in the
     * original, so the length iteration can change its draft without touching
     * the rules four playtests were run under.
     */
    foreach (['phases', 'rules', 'actions', 'conditions'] as $relation) {
        expect($draft->{$relation}()->pluck('id')->intersect($live->{$relation}()->pluck('id')))
            ->toBeEmpty("the clone shares a {$relation} row with its source");
    }

    expect($live->conditions()->where('name', 'Four cycles are over')->value('value'))->toBe('4')
        ->and($draft->conditions()->where('name', 'Three cycles are over')->value('value'))->toBe('3')
        ->and($draft->rules()->count())->toBeGreaterThan($live->rules()->count());
});

it('points every rule action at an economy action that resolves', function () {
    $this->seed(SampleDataSeeder::class);

    $economy = app(EconomyDirectory::class);
    $wired = 0;

    foreach (RuleSet::query()->with('version')->get() as $ruleSet) {
        $actions = $ruleSet->actions()->whereNotNull('economy_action_slug')->pluck('economy_action_slug')->all();
        $resources = $ruleSet->effects()->whereNotNull('economy_resource_slug')->pluck('economy_resource_slug')
            ->merge($ruleSet->requirements()->whereNotNull('economy_resource_slug')->pluck('economy_resource_slug'))
            ->unique()
            ->values()
            ->all();

        $wired += count($actions);

        expect($economy->unresolvedHandles($ruleSet->version, $actions, $resources))
            ->toBeEmpty("[{$ruleSet->name}] names an economy record that is not in its version's profile");
    }

    expect($wired)->toBeGreaterThan(0);
});

it('reads a cost off the economy rather than storing one', function () {
    $this->seed(SampleDataSeeder::class);

    $live = RuleSet::query()->where('name', 'Contract rework rules')->firstOrFail();

    $reference = app(EconomyDirectory::class)->resolveAction($live->version, 'fulfil-a-contract');

    expect($reference)->not->toBeNull()
        ->and($reference->isResolved)->toBeTrue()
        ->and($reference->label)->toBe('Fulfil a contract')
        ->and($reference->summary)->toContain('Crate');

    /*
     * The amounts in the summary come from the balance profile. The rules
     * themselves hold a direction and a handle — "+4 reputation" — and never a
     * bare quantity, so there is no second copy of the number to disagree with
     * the first.
     */
    $amounts = $live->effects()->whereNotNull('economy_resource_slug')->pluck('value')->filter();

    expect($amounts)->not->toBeEmpty()
        ->and($amounts->reject(fn (string $value): bool => str_starts_with($value, '+') || str_starts_with($value, '-')))
        ->toBeEmpty('a rule effect is holding a bare amount the economy already owns');
});

it('leaves the half-written rules with something for the validator to say', function () {
    $this->seed(SampleDataSeeder::class);

    $validator = app(RuleSetValidator::class);

    $errorsIn = fn (RuleSet $set): int => count(array_filter(
        $validator->validate($set),
        fn (ValidationError $finding): bool => $finding->isError(),
    ));

    /*
     * A draft nobody has finished has an action with no phase, which is an error
     * and is why it could not be activated. The two sets that *are* in play have
     * none, because activating them would have been refused otherwise.
     */
    expect($errorsIn(RuleSet::query()->where('name', 'Two-kiln rules')->firstOrFail()))->toBeGreaterThan(0);

    foreach (RuleSet::query()->where('status', 'active')->get() as $active) {
        expect($errorsIn($active))->toBe(0, "[{$active->name}] is in play with an error in it");
    }

    $warnings = count($validator->validate(RuleSet::query()->where('name', 'Two-kiln rules')->firstOrFail()));

    expect($warnings)->toBeGreaterThan(1);
});

it('edits rather than duplicates when it is run again', function () {
    $tally = fn (): array => [
        'users' => User::query()->count(),
        'games' => Game::query()->count(),
        'playtests' => Playtest::query()->count(),
        'iterations' => Iteration::query()->count(),
        'evidence' => DecisionEvidence::query()->count(),
        'profiles' => BalanceProfile::query()->count(),
        'snapshots' => BalanceSnapshot::query()->count(),
        'ruleSets' => RuleSet::query()->count(),
        'rules' => GameRule::query()->count(),
    ];

    $this->seed(SampleDataSeeder::class);

    $first = $tally();

    $this->seed(SampleDataSeeder::class);

    expect($tally())->toBe($first);
});
