<?php

use Database\Seeders\DesignFrameworkSeeder;
use Database\Seeders\FaDesignFrameworkSeeder;
use Database\Seeders\MechanicSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\SampleFaDataSeeder;
use Database\Seeders\SampleFaStudioSeeder;
use Database\Seeders\SampleStudioSeeder;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\Workspace\Domain\Models\Workspace;

/*
|--------------------------------------------------------------------------
| The Persian worked example
|--------------------------------------------------------------------------
|
| Two things are being held here, and neither is "the Persian text is present".
|
| The first is that authored content stays in the language it was authored in.
| A game's name, an observation, a note against an assumption — none of these
| go through `__()`, and a Persian studio's records must not quietly become
| English ones (or vice versa) because of how the seeders share machinery.
|
| The second is that every address is ASCII. `Str::slug('ایده‌پردازی')` is
| `aydhprdazy`: a URL segment nobody can read and a comparison key nobody can
| guess. Persian content therefore carries its own Latin slug, and a row that
| loses one fails here rather than in somebody's address bar.
|
*/

beforeEach(function () {
    $this->seed(MechanicSeeder::class);
    $this->seed(DesignFrameworkSeeder::class);
});

it('seeds a second methodology alongside the one Barkeep ships', function () {
    $this->seed(SampleFaDataSeeder::class);

    $editions = Framework::query()->pluck('name', 'slug');

    expect($editions)->toHaveKeys([DesignFrameworkSeeder::SLUG, FaDesignFrameworkSeeder::SLUG])
        ->and($editions[FaDesignFrameworkSeeder::SLUG])->toBe('مسیر کارگاه');

    $shape = fn (string $slug) => DesignPhaseDefinition::query()
        ->whereIn('framework_version_id', Framework::query()
            ->where('slug', $slug)
            ->join('framework_versions', 'frameworks.id', '=', 'framework_versions.framework_id')
            ->select('framework_versions.id'))
        ->count();

    /* Eight stages against ten: a second edition is authored, not translated. */
    expect($shape(FaDesignFrameworkSeeder::SLUG))->toBe(8)
        ->and($shape(DesignFrameworkSeeder::SLUG))->toBe(10);
});

it('points each studio at the methodology it follows', function () {
    $this->seed(SampleDataSeeder::class);
    $this->seed(SampleFaDataSeeder::class);

    $followed = GameFramework::query()
        ->with(['game', 'version.framework'])
        ->get()
        ->mapWithKeys(fn (GameFramework $adopted) => [
            $adopted->game->slug => $adopted->version->framework->slug,
        ]);

    expect($followed['karvansara'])->toBe(FaDesignFrameworkSeeder::SLUG)
        ->and($followed['harbourmaster'])->toBe(DesignFrameworkSeeder::SLUG);
});

it('gives every Persian record a Latin address', function () {
    $this->seed(SampleFaDataSeeder::class);

    $addresses = collect([
        'workspaces' => Workspace::query()->pluck('slug'),
        'games' => Game::query()->pluck('slug'),
        'frameworks' => Framework::query()->pluck('slug'),
        'design_phases' => DesignPhaseDefinition::query()->pluck('slug'),
        'design_criteria' => DesignCriterion::query()->pluck('slug'),
        'checklist_items' => ChecklistItem::query()->pluck('slug'),
        'resource_types' => ResourceType::query()->pluck('slug'),
        'balance_variables' => BalanceVariable::query()->pluck('slug'),
    ]);

    foreach ($addresses as $table => $slugs) {
        expect($slugs)->not->toBeEmpty("{$table} seeded nothing");

        $nonAscii = $slugs->reject(fn (string $slug) => (bool) preg_match('/^[a-z0-9-]+$/', $slug));

        expect($nonAscii)->toBeEmpty("{$table} has a non-Latin address: ".$nonAscii->implode(', '));
    }
});

it('keeps authored content in the language it was authored in', function () {
    $this->seed(SampleDataSeeder::class);
    $this->seed(SampleFaDataSeeder::class);

    $persian = '/\p{Arabic}/u';

    expect(Game::query()->where('slug', 'karvansara')->value('name'))->toMatch($persian)
        ->and(Game::query()->where('slug', 'harbourmaster')->value('name'))->not->toMatch($persian);

    /*
     * The criterion a Persian game is graded against is written in Persian, and the note somebody
     * wrote beside that grade is too. Both come out of the database as written.
     */
    $evaluation = GameFramework::query()
        ->whereIn('game_id', Game::query()->where('slug', 'karvansara')->select('id'))
        ->firstOrFail()
        ->criterionEvaluations()
        ->with('criterion')
        ->whereNotNull('notes')
        ->firstOrFail();

    expect($evaluation->criterion->title)->toMatch($persian)
        ->and($evaluation->notes)->toMatch($persian);
});

it('shares one design vocabulary between both studios', function () {
    $this->seed(SampleDataSeeder::class);
    $this->seed(SampleFaDataSeeder::class);

    $mechanicsOf = fn (string $slug) => Game::query()
        ->where('slug', $slug)
        ->firstOrFail()
        ->designRecord
        ->mechanics
        ->pluck('slug');

    $persian = $mechanicsOf('karvansara');
    $english = $mechanicsOf('harbourmaster');

    expect($persian)->not->toBeEmpty()
        ->and($persian->intersect($english))->not->toBeEmpty();

    /* Stored in English so both studios are recording the same fact; translated on the way out. */
    expect($persian->reject(fn (string $slug) => (bool) preg_match('/^[a-z0-9-]+$/', $slug)))->toBeEmpty();
});

it('cites Persian evidence that resolves to a real record', function () {
    $this->seed(SampleFaDataSeeder::class);

    $cited = DecisionEvidence::query()->where('type', '!=', EvidenceType::Note)->get();

    expect($cited)->not->toBeEmpty()
        ->and($cited->whereNull('reference_id'))->toBeEmpty();
});

it('lets one person hold different roles across the Persian workspaces', function () {
    $this->seed(SampleFaDataSeeder::class);

    $negar = User::query()
        ->where('email', 'negar@simorgh.test')
        ->firstOrFail();

    $roles = Workspace::query()->get()->mapWithKeys(fn (Workspace $workspace) => [
        $workspace->slug => $workspace->memberFor($negar)?->role->value,
    ]);

    expect($roles[SampleFaStudioSeeder::SIMORGH])->toBe('owner')
        ->and($roles[SampleFaStudioSeeder::OTAGH])->toBe('member');
});

it('keeps each studio out of the other\'s workspaces', function () {
    $this->seed(SampleDataSeeder::class);
    $this->seed(SampleFaDataSeeder::class);

    $gamesOf = fn (string $slug) => Game::query()
        ->whereIn('workspace_id', Workspace::query()->where('slug', $slug)->select('id'))
        ->pluck('slug');

    expect($gamesOf(SampleFaStudioSeeder::SIMORGH))->toContain('karvansara')
        ->and($gamesOf(SampleFaStudioSeeder::SIMORGH))->not->toContain('harbourmaster')
        ->and($gamesOf(SampleStudioSeeder::LANTERN))->toContain('harbourmaster')
        ->and($gamesOf(SampleStudioSeeder::LANTERN))->not->toContain('karvansara');
});

it('edits rather than duplicates when it is run again', function () {
    $tally = fn (): array => [
        'frameworks' => Framework::query()->count(),
        'phases' => DesignPhaseDefinition::query()->count(),
        'workspaces' => Workspace::query()->count(),
        'games' => Game::query()->count(),
        'evidence' => DecisionEvidence::query()->count(),
        'resources' => ResourceType::query()->count(),
    ];

    $this->seed(SampleFaDataSeeder::class);

    $first = $tally();

    $this->seed(SampleFaDataSeeder::class);

    expect($tally())->toBe($first);
});
