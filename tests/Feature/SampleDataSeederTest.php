<?php

use Database\Seeders\DesignFrameworkSeeder;
use Database\Seeders\MechanicSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\SampleStudioSeeder;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
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

it('edits rather than duplicates when it is run again', function () {
    $tally = fn (): array => [
        'users' => User::query()->count(),
        'games' => Game::query()->count(),
        'playtests' => Playtest::query()->count(),
        'iterations' => Iteration::query()->count(),
        'evidence' => DecisionEvidence::query()->count(),
        'profiles' => BalanceProfile::query()->count(),
        'snapshots' => BalanceSnapshot::query()->count(),
    ];

    $this->seed(SampleDataSeeder::class);

    $first = $tally();

    $this->seed(SampleDataSeeder::class);

    expect($tally())->toBe($first);
});
