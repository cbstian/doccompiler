<?php

use App\HealthChecks\ExtractionBinariesCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Health\Enums\Status;

uses(RefreshDatabase::class);

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

it('fails when an extraction binary is not executable', function (): void {
    config()->set('document-extractor.binaries', [
        'missing-binary' => storage_path('missing-binary'),
    ]);

    $result = ExtractionBinariesCheck::new()->run();

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->meta)->toBe(['missing-binary' => false]);
});
