<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocumentExtraction;
use App\Models\ApiClient;
use App\Models\ExtractionJob;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $client = $this->apiClient($request);
        $file = $request->file('document');
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $disk = config('document-extractor.storage.disk', 'local');
        $storagePath = Storage::disk($disk)->putFileAs('document-extractor/tmp', $file, "{$uuid}.{$extension}");
        $detectedMimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        $job = ExtractionJob::create([
            'uuid' => $uuid,
            'api_client_id' => $client->id,
            'original_filename' => $file->getClientOriginalName(),
            'extension' => $extension,
            'declared_mime_type' => $file->getClientMimeType(),
            'detected_mime_type' => $detectedMimeType ?: null,
            'size_bytes' => $file->getSize(),
            'storage_path' => $storagePath,
            'status' => JobStatus::Pending,
        ]);

        $job->events()->create(['event' => 'queued']);

        ProcessDocumentExtraction::dispatch($job)
            ->onConnection(config('document-extractor.queue.connection'))
            ->onQueue(config('document-extractor.queue.queue'));

        return response()->json([
            'uuid' => $job->uuid,
            'status' => $job->status->value,
            'status_url' => route('api.documents.show', ['uuid' => $job->uuid], false),
        ], 202);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $job = $this->findClientJob($request, $uuid);

        return response()->json($this->serializeJob($job));
    }

    public function content(Request $request, string $uuid): JsonResponse
    {
        $job = $this->findClientJob($request, $uuid)->load('result');

        if ($job->status !== JobStatus::Completed || ! $job->result) {
            return response()->json([
                'error' => [
                    'code' => 'CONTENT_NOT_READY',
                    'message' => 'El contenido todavía no está disponible.',
                ],
            ], 409);
        }

        $content = $job->result->content;

        if ($content === null && $job->result->content_path !== null) {
            $content = Storage::disk($job->result->content_disk ?? config('document-extractor.storage.disk', 'local'))
                ->get($job->result->content_path);
        }

        return response()->json([
            'uuid' => $job->uuid,
            'output_format' => $job->result->output_format->value,
            'content' => $content,
            'char_count' => $job->result->char_count,
            'estimated_tokens' => $job->result->estimated_tokens,
            'headings_count' => $job->result->headings_count,
            'tables_count' => $job->result->tables_count,
            'language_detected' => $job->result->language_detected,
        ]);
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $job = $this->findClientJob($request, $uuid)->load('result');
        $disk = config('document-extractor.storage.disk', 'local');

        if ($job->storage_path) {
            Storage::disk($disk)->delete($job->storage_path);
        }

        if ($job->result?->content_path) {
            Storage::disk($job->result->content_disk ?? $disk)->delete($job->result->content_path);
        }

        $job->delete();

        return response()->noContent();
    }

    private function apiClient(Request $request): ApiClient
    {
        return $request->attributes->get('apiClient');
    }

    private function findClientJob(Request $request, string $uuid): ExtractionJob
    {
        return ExtractionJob::query()
            ->where('api_client_id', $this->apiClient($request)->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeJob(ExtractionJob $job): array
    {
        return [
            'uuid' => $job->uuid,
            'status' => $job->status->value,
            'original_filename' => $job->original_filename,
            'extension' => $job->extension,
            'declared_mime_type' => $job->declared_mime_type,
            'detected_mime_type' => $job->detected_mime_type,
            'size_bytes' => $job->size_bytes,
            'extractor_driver' => $job->extractor_driver?->value,
            'ocr_used' => $job->ocr_used,
            'pages_total' => $job->pages_total,
            'pages_ocr' => $job->pages_ocr,
            'error_code' => $job->error_code?->value,
            'error_message' => $job->error_message,
            'started_at' => $job->started_at?->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
            'duration_ms' => $job->duration_ms,
            'created_at' => $job->created_at?->toIso8601String(),
            'updated_at' => $job->updated_at?->toIso8601String(),
        ];
    }
}
