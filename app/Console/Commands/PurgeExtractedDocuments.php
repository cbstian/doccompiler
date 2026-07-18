<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\ExtractionJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('documents:purge')]
#[Description('Elimina archivos originales de jobs de extracción cuya retención ha expirado')]
class PurgeExtractedDocuments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) config('document-extractor.storage.retention_days', 7);
        $cutoff = now()->subDays($retentionDays);

        $this->info("Purgando documentos anteriores a {$cutoff->toDateString()} ({$retentionDays} días de retención)...");

        $purged = 0;

        ExtractionJob::query()
            ->whereIn('status', [JobStatus::Completed->value, JobStatus::Failed->value])
            ->whereNotNull('storage_path')
            ->where('completed_at', '<', $cutoff)
            ->chunkById(100, function ($jobs) use (&$purged) {
                foreach ($jobs as $job) {
                    $disk = config('document-extractor.storage.disk', 'local');

                    if ($job->storage_path && Storage::disk($disk)->exists($job->storage_path)) {
                        Storage::disk($disk)->delete($job->storage_path);
                    }

                    $job->events()->create([
                        'event' => 'purged',
                        'payload' => [
                            'purged_at' => now()->toIso8601String(),
                            'retention_days' => (int) config('document-extractor.storage.retention_days', 7),
                        ],
                    ]);

                    $job->update(['storage_path' => null]);

                    $purged++;
                }
            });

        $this->info("Purga completada. {$purged} archivos originales eliminados.");

        return Command::SUCCESS;
    }
}
