<?php

namespace App\HealthChecks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class ExtractionBinariesCheck extends Check
{
    protected ?string $binaryName = null;

    protected ?string $binaryPath = null;

    public function binary(string $name): static
    {
        $this->binaryName = $name;
        $this->binaryPath = config("document-extractor.binaries.{$name}");

        return $this;
    }

    public function run(): Result
    {
        // Modo individual: verificar un solo binario
        if ($this->binaryName !== null) {
            return $this->checkSingleBinary();
        }

        // Modo agregado (legado): verificar todos los binarios
        return $this->checkAllBinaries();
    }

    protected function checkSingleBinary(): Result
    {
        $isExecutable = $this->binaryPath !== null && is_executable($this->binaryPath);

        $result = Result::make()
            ->meta([
                'binary' => $this->binaryName,
                'path' => $this->binaryPath,
                'executable' => $isExecutable,
            ]);

        if (! $isExecutable) {
            $message = $this->binaryPath === null
                ? "Binary '{$this->binaryName}' is not configured."
                : "Binary '{$this->binaryName}' is not executable at: {$this->binaryPath}";

            return $result->failed($message);
        }

        return $result->ok("Binary '{$this->binaryName}' is executable.");
    }

    protected function checkAllBinaries(): Result
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
