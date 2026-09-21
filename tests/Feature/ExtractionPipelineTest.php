<?php

use App\Enums\ErrorCode;
use App\Enums\ExtractorDriver;
use App\Enums\JobStatus;
use App\Jobs\ProcessDocumentExtraction;
use App\Models\ApiClient;
use App\Models\ExtractionJob;
use App\Services\Extractors\ExtractorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('processes a txt extraction job end-to-end', function () {
    Storage::fake('local');
    config()->set('document-extractor.storage.disk', 'local');

    $client = ApiClient::factory()->create();
    $uuid = (string) Str::uuid();
    $relative = "document-extractor/tmp/{$uuid}.txt";
    Storage::disk('local')->put($relative, file_get_contents(base_path('tests/Fixtures/sample.txt')));

    $job = ExtractionJob::factory()->for($client, 'apiClient')->create([
        'uuid' => $uuid,
        'extension' => 'txt',
        'original_filename' => 'sample.txt',
        'storage_path' => $relative,
        'status' => JobStatus::Pending,
    ]);

    (new ProcessDocumentExtraction($job))->handle(
        app(ExtractorManager::class),
        app(\App\Services\Extractors\TextNormalizer::class),
    );

    $job->refresh();

    expect($job->status)->toBe(JobStatus::Completed)
        ->and($job->extractor_driver)->toBe(ExtractorDriver::PlainText)
        ->and($job->result)->not->toBeNull()
        ->and($job->result->content)->toContain('Hola DocCompiler');
});

it('processes a markdown extraction job end-to-end', function () {
    Storage::fake('local');
    config()->set('document-extractor.storage.disk', 'local');

    $client = ApiClient::factory()->create();
    $uuid = (string) Str::uuid();
    $relative = "document-extractor/tmp/{$uuid}.md";
    Storage::disk('local')->put($relative, file_get_contents(base_path('tests/Fixtures/sample.md')));

    $job = ExtractionJob::factory()->for($client, 'apiClient')->create([
        'uuid' => $uuid,
        'extension' => 'md',
        'original_filename' => 'sample.md',
        'storage_path' => $relative,
        'status' => JobStatus::Pending,
    ]);

    (new ProcessDocumentExtraction($job))->handle(
        app(ExtractorManager::class),
        app(\App\Services\Extractors\TextNormalizer::class),
    );

    $job->refresh();

    expect($job->status)->toBe(JobStatus::Completed)
        ->and($job->extractor_driver)->toBe(ExtractorDriver::Markdown)
        ->and($job->result->content)->toContain('# Documento de prueba');
});

it('fails honestly for formats not implemented yet', function () {
    Storage::fake('local');
    config()->set('document-extractor.storage.disk', 'local');

    $client = ApiClient::factory()->create();
    $uuid = (string) Str::uuid();
    $relative = "document-extractor/tmp/{$uuid}.docx";
    Storage::disk('local')->put($relative, 'PK fake zip');

    $job = ExtractionJob::factory()->for($client, 'apiClient')->create([
        'uuid' => $uuid,
        'extension' => 'docx',
        'original_filename' => 'sample.docx',
        'storage_path' => $relative,
        'status' => JobStatus::Pending,
    ]);

    (new ProcessDocumentExtraction($job))->handle(
        app(ExtractorManager::class),
        app(\App\Services\Extractors\TextNormalizer::class),
    );

    $job->refresh();

    expect($job->status)->toBe(JobStatus::Failed)
        ->and($job->error_code)->toBe(ErrorCode::UnsupportedFormat)
        ->and($job->error_message)->toContain('aún no está implementado');
});
