<?php

namespace App\HealthChecks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class ExtractionBinariesCheck extends Check
{
    public function run(): Result
    {
        $binaries = collect(config('document-extractor.binaries', []))
            ->mapWithKeys(fn (string $path, string $name): array => [$name => is_executable($path)]);

        $missingBinaries = $binaries
            ->filter(fn (bool $isExecutable): bool => ! $isExecutable)
            ->keys();

        $result = Result::make()->meta($binaries->toArray());

        if ($missingBinaries->isNotEmpty()) {
            return $result->failed('Missing executable extraction binaries: '.$missingBinaries->join(', '));
        }

        return $result->ok();
    }
}
