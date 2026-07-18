<?php

namespace App\Jobs;

use App\Enums\ErrorCode;
use App\Enums\JobStatus;
use App\Models\ExtractionJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(): void
    {
        $startedAt = now();

        $this->extractionJob->update([
            'status' => JobStatus::Processing,
            'started_at' => $startedAt,
        ]);

        $this->extractionJob->events()->create([
            'event' => 'failed',
            'payload' => ['reason' => 'Extractor pipeline not implemented yet.'],
        ]);

        $this->extractionJob->update([
            'status' => JobStatus::Failed,
            'error_code' => ErrorCode::UnsupportedFormat,
            'error_message' => 'Extractor pipeline not implemented yet.',
            'completed_at' => now(),
            'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
        ]);
    }
}
