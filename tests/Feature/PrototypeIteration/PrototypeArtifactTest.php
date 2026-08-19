<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\ValueObjects\ArtifactMetadata;
use Modules\Workspace\Domain\Models\Workspace;

beforeEach(function () {
    Storage::fake();

    $this->designer = User::factory()->create();
    $this->workspace = Workspace::factory()->ownedBy($this->designer)->withSlug('studio')->create();
    $this->game = Game::factory()->inWorkspace($this->workspace)->withSlug('bears')->active()->create();
    $this->prototype = Prototype::factory()->forGame($this->game)->create();
    $this->version = PrototypeVersion::factory()->nextFor($this->prototype)->create();

    $this->base = "/api/v1/workspaces/studio/games/bears/prototypes/{$this->prototype->id}"
        ."/versions/{$this->version->version_number}/artifacts";
});

it('attaches a file to a state of a prototype', function () {
    $file = UploadedFile::fake()->create('card-fronts.pdf', 120, 'application/pdf');

    $this->actingAs($this->designer)->post($this->base, ['file' => $file])
        ->assertCreated()
        ->assertJsonPath('data.name', 'card-fronts.pdf')
        ->assertJsonPath('data.type', PrototypeArtifactType::Pdf->value)
        ->assertJsonPath('data.original_filename', 'card-fronts.pdf')
        ->assertJsonPath('data.prototype_version_id', $this->version->id);

    $artifact = PrototypeArtifact::query()->sole();

    Storage::assertExists($artifact->storage_reference);
});

it('derives the name and kind from the upload when nobody gives them', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->image('board.png')])
        ->assertCreated()
        ->assertJsonPath('data.name', 'board.png')
        ->assertJsonPath('data.type', PrototypeArtifactType::Image->value);
});

it('takes the name and kind the designer chose over the derived ones', function () {
    $this->actingAs($this->designer)->post($this->base, [
        'file' => UploadedFile::fake()->create('export.bin', 10),
        'name' => 'Tabletop Simulator save',
        'type' => 'build',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Tabletop Simulator save')
        ->assertJsonPath('data.type', PrototypeArtifactType::Build->value);
});

it('records what the upload reported about the file', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('sheet.pdf', 2048, 'application/pdf')])
        ->assertCreated()
        ->assertJsonPath('data.mime_type', 'application/pdf')
        ->assertJsonPath('data.size', 2048 * 1024)
        ->assertJsonPath('data.size_label', '2.0 MB');
});

/**
 * The stored name is ours end to end — a uuid plus a normalised extension — so a hostile filename is a
 * non-event rather than something to sanitise. The original is kept for display only.
 */
it('never lets a client\'s filename reach the stored path', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('../../../etc/passwd', 1)])
        ->assertCreated();

    $artifact = PrototypeArtifact::query()->sole();

    expect($artifact->storage_reference)->toStartWith('prototype-artifacts/')
        ->and($artifact->storage_reference)->not->toContain('..')
        ->and($artifact->storage_reference)->not->toContain('passwd')
        ->and($artifact->metadata()->originalFilename)->toBe('passwd');
});

it('publishes no path or url a client could construct a link from', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('sheet.pdf', 1)])
        ->assertCreated()
        ->assertJsonMissingPath('data.storage_reference')
        ->assertJsonMissingPath('data.url');
});

it('requires a file', function () {
    $this->actingAs($this->designer)->post($this->base, [])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('file');
});

it('refuses a file larger than the ceiling', function () {
    $tooBig = UploadedFile::fake()->create('huge.pdf', ArtifactMetadata::MAX_SIZE_KILOBYTES + 1);

    $this->actingAs($this->designer)->post($this->base, ['file' => $tooBig])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('file');

    expect(PrototypeArtifact::query()->count())->toBe(0);
});

it('lists a state\'s files in upload order', function () {
    $first = PrototypeArtifact::factory()->forVersion($this->version)->create([
        'name' => 'fronts.pdf',
        'created_at' => now()->subHour(),
    ]);
    $second = PrototypeArtifact::factory()->forVersion($this->version)->create(['name' => 'backs.pdf']);

    $this->actingAs($this->designer)->getJson($this->base)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.1.id', $second->id);
});

it('removes a file and the bytes behind it', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('sheet.pdf', 1)])
        ->assertCreated();

    $artifact = PrototypeArtifact::query()->sole();
    $reference = $artifact->storage_reference;

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$artifact->id)
        ->assertNoContent();

    expect(PrototypeArtifact::query()->count())->toBe(0);
    Storage::assertMissing($reference);
});

/**
 * The one place the immutability rule deliberately does not reach. A print sheet filed later documents
 * what the version was; it does not change it.
 */
it('accepts a file against a state that has already been iterated on', function () {
    Iteration::factory()
        ->forPrototypeVersion($this->version)
        ->create();

    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('late-sheet.pdf', 1)])
        ->assertCreated();
});

it('refuses a file against an archived prototype', function () {
    $archived = Prototype::factory()->forGame($this->game)->archived()->create();
    $version = PrototypeVersion::factory()->nextFor($archived)->create();

    $this->actingAs($this->designer)
        ->post(
            "/api/v1/workspaces/studio/games/bears/prototypes/{$archived->id}/versions/{$version->version_number}/artifacts",
            ['file' => UploadedFile::fake()->create('sheet.pdf', 1)],
        )
        ->assertForbidden();

    expect(PrototypeArtifact::query()->count())->toBe(0);
});

it('404s on an artifact belonging to another prototype state', function () {
    $otherVersion = PrototypeVersion::factory()->nextFor($this->prototype)->create();
    $theirArtifact = PrototypeArtifact::factory()->forVersion($otherVersion)->create();

    $this->actingAs($this->designer)->deleteJson($this->base.'/'.$theirArtifact->id)
        ->assertNotFound();

    expect(PrototypeArtifact::query()->count())->toBe(1);
});

it('streams a download behind the policy, under the uploaded name', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('card-fronts.pdf', 4, 'application/pdf')])
        ->assertCreated();

    $artifact = PrototypeArtifact::query()->sole();

    $url = "/app/workspaces/studio/games/bears/prototypes/{$this->prototype->id}"
        ."/versions/{$this->version->version_number}/artifacts/{$artifact->id}";

    $this->actingAs($this->designer)->get($url)
        ->assertOk()
        ->assertDownload('card-fronts.pdf');
});

it('refuses a download to somebody outside the workspace', function () {
    $this->actingAs($this->designer)
        ->post($this->base, ['file' => UploadedFile::fake()->create('secret-art.pdf', 4)])
        ->assertCreated();

    $artifact = PrototypeArtifact::query()->sole();
    $outsider = User::factory()->create();

    $url = "/app/workspaces/studio/games/bears/prototypes/{$this->prototype->id}"
        ."/versions/{$this->version->version_number}/artifacts/{$artifact->id}";

    $this->actingAs($outsider)->get($url)->assertNotFound();
});

it('404s a download whose file has gone missing rather than failing mid-stream', function () {
    $artifact = PrototypeArtifact::factory()->forVersion($this->version)->create();

    $url = "/app/workspaces/studio/games/bears/prototypes/{$this->prototype->id}"
        ."/versions/{$this->version->version_number}/artifacts/{$artifact->id}";

    $this->actingAs($this->designer)->get($url)->assertNotFound();
});
