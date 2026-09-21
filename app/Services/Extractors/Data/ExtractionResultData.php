<?php

namespace App\Services\Extractors\Data;

use App\Enums\ExtractorDriver;
use App\Enums\OutputFormat;

readonly class ExtractionResultData
{
    public function __construct(
        public string $content,
        public ExtractorDriver $driver,
        public OutputFormat $outputFormat,
        public bool $ocrUsed = false,
        public ?int $pagesTotal = null,
        public ?int $pagesOcr = null,
        public int $headingsCount = 0,
        public int $tablesCount = 0,
        public ?string $languageDetected = null,
    ) {}
}
