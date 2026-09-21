<?php

use App\Enums\ExtractorDriver;
use App\Enums\OutputFormat;
use App\Services\Extractors\Drivers\MarkdownExtractor;
use App\Services\Extractors\TextNormalizer;

it('extracts markdown from an md fixture', function () {
    $path = base_path('tests/Fixtures/sample.md');
    $extractor = new MarkdownExtractor(new TextNormalizer);
    $result = $extractor->extract($path);

    expect($result->driver)->toBe(ExtractorDriver::Markdown)
        ->and($result->outputFormat)->toBe(OutputFormat::Markdown)
        ->and($result->content)->toContain('# Documento de prueba')
        ->and($result->headingsCount)->toBeGreaterThan(0)
        ->and($result->tablesCount)->toBeGreaterThan(0);
});
