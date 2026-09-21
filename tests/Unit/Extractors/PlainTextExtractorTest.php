<?php

use App\Enums\ExtractorDriver;
use App\Enums\OutputFormat;
use App\Services\Extractors\Drivers\PlainTextExtractor;
use App\Services\Extractors\TextNormalizer;

it('extracts plain text from a txt fixture', function () {
    $path = base_path('tests/Fixtures/sample.txt');
    $extractor = new PlainTextExtractor(new TextNormalizer);
    $result = $extractor->extract($path);

    expect($result->driver)->toBe(ExtractorDriver::PlainText)
        ->and($result->outputFormat)->toBe(OutputFormat::PlainText)
        ->and($result->content)->toContain('Hola DocCompiler')
        ->and($result->content)->toContain('texto plano');
});
