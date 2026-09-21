<?php

namespace App\Jobs;

use App\Enums\ErrorCode;
use App\Enums\JobStatus;
use App\Exceptions\ExtractionException;
use App\Models\ExtractionJob;
use App\Services\Extractors\ExtractorManager;
use App\Services\Extractors\TextNormalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDocumentExtraction implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public ExtractionJob $extractionJob) {}

    /**
     * Execute the job.
     */
    public function handle(ExtractorManager $extractors, TextNormalizer $normalizer): void
    {
        $startedAt = now();

        $this->extractionJob->update([
            'status' => JobStatus::Processing,
            'started_at' => $startedAt,
        ]);

        $this->extractionJob->events()->create([
            'event' => 'processing_started',
            'payload' => ['extension' => $this->extractionJob->extension],
        ]);

        try {
            $disk = config('document-extractor.storage.disk', 'local');
            $relativePath = $this->extractionJob->storage_path;

            if (! $relativePath || ! Storage::disk($disk)->exists($relativePath)) {
                throw new ExtractionException(
                    ErrorCode::CorruptedFile,
                    'El archivo original no está disponible en almacenamiento.',
                );
            }

            $absolutePath = Storage::disk($disk)->path($relativePath);
            $result = $extractors->forExtension((string) $this->extractionJob->extension)
                ->extract($absolutePath);

            $content = $result->content;
            $charCount = $normalizer->charCount($content);
            $spilloverThreshold = (int) config(
                'document-extractor.storage.content_spillover_threshold_bytes',
                2 * 1024 * 1024,
            );

            $contentDisk = null;
            $contentPath = null;
            $dbContent = $content;

            if (strlen($content) > $spilloverThreshold) {
                $contentDisk = config('document-extractor.storage.result_disk', $disk);
                $contentPath = "document-extractor/results/{$this->extractionJob->uuid}.txt";
                Storage::disk($contentDisk)->put($contentPath, $content);
                $dbContent = null;
            }

            $this->extractionJob->result()->create([
                'output_format' => $result->outputFormat,
                'content' => $dbContent,
                'content_disk' => $contentDisk,
                'content_path' => $contentPath,
                'char_count' => $charCount,
                'estimated_tokens' => $normalizer->estimatedTokens($content),
                'headings_count' => $result->headingsCount,
                'tables_count' => $result->tablesCount,
                'language_detected' => $result->languageDetected,
            ]);

            $this->extractionJob->update([
                'status' => JobStatus::Completed,
                'extractor_driver' => $result->driver,
                'ocr_used' => $result->ocrUsed,
                'pages_total' => $result->pagesTotal,
                'pages_ocr' => $result->pagesOcr,
                'error_code' => null,
                'error_message' => null,
                'completed_at' => now(),
                'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
            ]);

            $this->extractionJob->events()->create([
                'event' => 'completed',
                'payload' => [
                    'driver' => $result->driver->value,
                    'char_count' => $charCount,
                ],
            ]);
        } catch (ExtractionException $e) {
            $this->failJob($startedAt, $e->errorCode, $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            $this->failJob($startedAt, ErrorCode::CorruptedFile, 'Error inesperado durante la extracción: '.$e->getMessage());
        }
    }

    private function failJob(\Illuminate\Support\Carbon $startedAt, ErrorCode $errorCode, string $message): void
    {
        $this->extractionJob->events()->create([
            'event' => 'failed',
            'payload' => [
                'error_code' => $errorCode->value,
                'reason' => $message,
            ],
        ]);

        $this->extractionJob->update([
            'status' => JobStatus::Failed,
            'error_code' => $errorCode,
            'error_message' => $message,
            'completed_at' => now(),
            'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
        ]);
    }
}
