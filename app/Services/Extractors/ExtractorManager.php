<?php

namespace App\Services\Extractors;

use App\Enums\ErrorCode;
use App\Exceptions\ExtractionException;
use App\Services\Extractors\Contracts\DocumentExtractorContract;
use App\Services\Extractors\Drivers\MarkdownExtractor;
use App\Services\Extractors\Drivers\PdfNativeExtractor;
use App\Services\Extractors\Drivers\PlainTextExtractor;
use Illuminate\Support\Manager;

class ExtractorManager extends Manager
{
    /**
     * Resolve an extractor for a file extension.
     *
     * Supported in Phase 1: txt, md, pdf (native text layer via pdftotext).
     * Not yet implemented: docx, doc, xlsx, xls, csv, pdf OCR.
     *
     * @throws ExtractionException
     */
    public function forExtension(string $extension): DocumentExtractorContract
    {
        $extension = strtolower(ltrim($extension, '.'));

        $driver = match ($extension) {
            'txt' => 'plain_text',
            'md', 'markdown' => 'markdown',
            'pdf' => 'pdf_native',
            'docx', 'doc', 'xlsx', 'xls', 'csv' => throw new ExtractionException(
                ErrorCode::UnsupportedFormat,
                "El formato .{$extension} está declarado en la API pero su extractor aún no está implementado (Phase 1: TXT, MD, PDF nativo).",
            ),
            default => throw new ExtractionException(
                ErrorCode::UnsupportedFormat,
                "Formato no soportado: .{$extension}",
            ),
        };

        /** @var DocumentExtractorContract $extractor */
        $extractor = $this->driver($driver);

        return $extractor;
    }

    public function getDefaultDriver(): string
    {
        return 'plain_text';
    }

    protected function createPlainTextDriver(): DocumentExtractorContract
    {
        return $this->container->make(PlainTextExtractor::class);
    }

    protected function createMarkdownDriver(): DocumentExtractorContract
    {
        return $this->container->make(MarkdownExtractor::class);
    }

    protected function createPdfNativeDriver(): DocumentExtractorContract
    {
        return $this->container->make(PdfNativeExtractor::class);
    }
}
