<?php

namespace App\Services\Extractors\Drivers;

use App\Enums\ErrorCode;
use App\Enums\ExtractorDriver;
use App\Enums\OutputFormat;
use App\Exceptions\ExtractionException;
use App\Services\Extractors\Contracts\DocumentExtractorContract;
use App\Services\Extractors\Data\ExtractionResultData;
use App\Services\Extractors\TextNormalizer;

class MarkdownExtractor implements DocumentExtractorContract
{
    public function __construct(private readonly TextNormalizer $normalizer) {}

    public function extract(string $absolutePath): ExtractionResultData
    {
        if (! is_readable($absolutePath)) {
            throw new ExtractionException(
                ErrorCode::CorruptedFile,
                "No se puede leer el archivo Markdown: {$absolutePath}",
            );
        }

        $raw = file_get_contents($absolutePath);

        if ($raw === false) {
            throw new ExtractionException(
                ErrorCode::CorruptedFile,
                'No se pudo leer el contenido del archivo Markdown.',
            );
        }

        // Phase 1: keep Markdown as-is after light normalization (no CommonMark rewrite yet).
        $content = $this->normalizer->normalize($raw);

        return new ExtractionResultData(
            content: $content,
            driver: ExtractorDriver::Markdown,
            outputFormat: OutputFormat::Markdown,
            headingsCount: $this->normalizer->headingsCount($content),
            tablesCount: $this->normalizer->tablesCount($content),
        );
    }
}
