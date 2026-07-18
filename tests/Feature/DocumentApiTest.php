<?php

use App\Enums\JobStatus;
use App\Jobs\ProcessDocumentExtraction;
use App\Models\ApiClient;
use App\Models\ExtractionJob;
use App\Models\ExtractionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createApiClientWithToken(string $plainToken = 'test-token'): ApiClient
{
    return ApiClient::factory()->create([
        'token' => hash('sha256', $plainToken),
    ]);
}

it('returns health information', function () {
    $response = $this->getJson(route('api.health', absolute: false));

    $response->assertSuccessful()
        ->assertJsonStructure(['binaries', 'queue_connection', 'disk_free_mb']);
});

it('rejects requests without a valid client token', function () {
    $this->getJson('/api/v1/documents/'.Str::uuid())
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

it('updates last used timestamp for valid client tokens', function () {
    $client = createApiClientWithToken();

    $this->withToken('test-token')
        ->getJson('/api/v1/documents/'.Str::uuid())
        ->assertNotFound();

    expect($client->fresh()->last_used_at)->not->toBeNull();
});

it('stores an uploaded document and dispatches extraction', function () {
    Queue::fake();
    Storage::fake('local');
    config()->set('document-extractor.storage.disk', 'local');
    config()->set('document-extractor.queue.connection', 'sync');
    config()->set('document-extractor.queue.queue', 'documents');

    $client = createApiClientWithToken();
    $file = UploadedFile::fake()->createWithContent('sample.txt', 'Contenido de prueba');

    $response = $this->withToken('test-token')
        ->postJson('/api/v1/documents', ['document' => $file]);

    $response->assertStatus(202)
        ->assertJsonPath('status', JobStatus::Pending->value)
        ->assertJsonStructure(['uuid', 'status', 'status_url']);

    $job = ExtractionJob::query()->where('uuid', $response->json('uuid'))->firstOrFail();

    expect($job->api_client_id)->toBe($client->id)
        ->and($job->events()->where('event', 'queued')->exists())->toBeTrue();

    Storage::disk('local')->assertExists($job->storage_path);

    Queue::assertPushed(ProcessDocumentExtraction::class);
});

it('only shows documents owned by the authenticated client', function () {
    $client = createApiClientWithToken();
    $otherClient = createApiClientWithToken('other-token');
    $job = ExtractionJob::factory()->for($client, 'apiClient')->create();
    $otherJob = ExtractionJob::factory()->for($otherClient, 'apiClient')->create();

    $this->withToken('test-token')
        ->getJson("/api/v1/documents/{$job->uuid}")
        ->assertSuccessful()
        ->assertJsonPath('uuid', $job->uuid);

    $this->withToken('test-token')
        ->getJson("/api/v1/documents/{$otherJob->uuid}")
        ->assertNotFound();
});

it('returns completed document content from the database', function () {
    $client = createApiClientWithToken();
    $job = ExtractionJob::factory()
        ->for($client, 'apiClient')
        ->create(['status' => JobStatus::Completed]);

    ExtractionResult::factory()->for($job, 'extractionJob')->create([
        'content' => 'Texto extraído',
        'char_count' => 14,
        'estimated_tokens' => 4,
    ]);

    $this->withToken('test-token')
        ->getJson("/api/v1/documents/{$job->uuid}/content")
        ->assertSuccessful()
        ->assertJsonPath('content', 'Texto extraído');
});

it('deletes the document and stored files for the authenticated client', function () {
    Storage::fake('local');
    config()->set('document-extractor.storage.disk', 'local');

    $client = createApiClientWithToken();
    Storage::disk('local')->put('document-extractor/tmp/original.txt', 'original');
    Storage::disk('local')->put('document-extractor/results/result.txt', 'result');

    $job = ExtractionJob::factory()->for($client, 'apiClient')->create([
        'storage_path' => 'document-extractor/tmp/original.txt',
    ]);

    ExtractionResult::factory()->for($job, 'extractionJob')->create([
        'content' => null,
        'content_disk' => 'local',
        'content_path' => 'document-extractor/results/result.txt',
    ]);

    $this->withToken('test-token')
        ->deleteJson("/api/v1/documents/{$job->uuid}")
        ->assertNoContent();

    Storage::disk('local')->assertMissing('document-extractor/tmp/original.txt');
    Storage::disk('local')->assertMissing('document-extractor/results/result.txt');
    $this->assertDatabaseMissing('extraction_jobs', ['id' => $job->id]);
});

it('rejects files whose real mime type does not match their extension', function () {
    Queue::fake();
    Storage::fake('local');
    $client = createApiClientWithToken();
    $file = UploadedFile::fake()->createWithContent('sample.pdf', 'not a pdf');

    $this->withToken('test-token')
        ->postJson('/api/v1/documents', ['document' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document');

    expect($client->fresh()->last_used_at)->not->toBeNull();
});
