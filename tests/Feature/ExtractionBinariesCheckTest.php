<?php

use App\HealthChecks\ExtractionBinariesCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Health\Enums\Status;

uses(RefreshDatabase::class);

// ─── Modo agregado (legado) ─────────────────────────────────────────

it('passes when all extraction binaries are executable', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'extractor-binary-');
    chmod($binaryPath, 0755);

    config()->set('document-extractor.binaries', [
        'test-binary' => $binaryPath,
    ]);

    $result = ExtractionBinariesCheck::new()->run();

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($result->meta)->toBe(['test-binary' => true]);

    unlink($binaryPath);
});

it('fails when an extraction binary is not executable in aggregate mode', function (): void {
    config()->set('document-extractor.binaries', [
        'missing-binary' => storage_path('missing-binary'),
    ]);

    $result = ExtractionBinariesCheck::new()->run();

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->meta)->toBe(['missing-binary' => false]);
});

// ─── Modo individual (cápsulas separadas) ────────────────────────────

it('passes when a single binary is executable', function (): void {
    $binaryPath = tempnam(sys_get_temp_dir(), 'extractor-binary-');
    chmod($binaryPath, 0755);

    config()->set('document-extractor.binaries', [
        'pdftotext' => $binaryPath,
    ]);

    $result = ExtractionBinariesCheck::new()
        ->name('extraction-binary-pdftotext')
        ->label('Extraction Binary: pdftotext')
        ->binary('pdftotext')
        ->run();

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($result->meta['binary'])->toBe('pdftotext')
        ->and($result->meta['executable'])->toBeTrue();
});

it('fails when a single binary is not executable', function (): void {
    $missingPath = storage_path('missing-binary');

    config()->set('document-extractor.binaries', [
        'pdftotext' => $missingPath,
    ]);

    $result = ExtractionBinariesCheck::new()
        ->name('extraction-binary-pdftotext')
        ->label('Extraction Binary: pdftotext')
        ->binary('pdftotext')
        ->run();

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->meta['binary'])->toBe('pdftotext')
        ->and($result->meta['path'])->toBe($missingPath)
        ->and($result->meta['executable'])->toBeFalse();
});

it('fails when a configured binary name does not exist in config', function (): void {
    config()->set('document-extractor.binaries', []);

    $result = ExtractionBinariesCheck::new()
        ->name('extraction-binary-unknown')
        ->label('Extraction Binary: unknown')
        ->binary('unknown')
        ->run();

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->meta['binary'])->toBe('unknown')
        ->and($result->meta['path'])->toBeNull()
        ->and($result->meta['executable'])->toBeFalse();
});
