<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $binaries = collect(config('document-extractor.binaries', []))
            ->mapWithKeys(fn (string $path, string $name): array => [$name => is_executable($path)]);

        return response()->json([
            'binaries' => $binaries,
            'queue_connection' => config('document-extractor.queue.connection'),
            'disk_free_mb' => round(disk_free_space(storage_path()) / 1024 / 1024),
        ]);
    }
}
