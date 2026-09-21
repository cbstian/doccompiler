<?php

namespace App\Services\Extractors\Drivers;

use App\Enums\ErrorCode;
use App\Enums\ExtractorDriver;
use App\Enums\OutputFormat;
use App\Exceptions\ExtractionException;
use App\Services\Extractors\Contracts\DocumentExtractorContract;
use App\Services\Extractors\Data\ExtractionResultData;
use App\Services\Extractors\TextNormalizer;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PdfNativeExtractor implements DocumentExtractorContract
{
    public function __construct(private readonly TextNormalizer $normalizer) {}

    public function extract(string $absolutePath): ExtractionResultData
    {
        if (! is_readable($absolutePath)) {
            throw new ExtractionException(
                ErrorCode::CorruptedFile,
                "No se puede leer el PDF: {$absolutePath}",
            );
        }

        $header = file_get_contents($absolutePath, false, null, 0, 5);

        if ($header === false || ! str_starts_with($header, '%PDF')) {
            throw new ExtractionException(
                ErrorCode::CorruptedFile,
                'El archivo no parece ser un PDF válido (magic bytes).',
            );
        }

        $pdftotext = (string) config('document-extractor.binaries.pdftotext', '/usr/bin/pdftotext');

        if (! is_executable($pdftotext)) {
            throw new ExtractionException(
                ErrorCode::OcrBinaryMissing,
                "El binario pdftotext no está disponible en: {$pdftotext}. Instala poppler-utils.",
            );
        }

        $timeout = (int) config('document-extractor.process_timeout_seconds', 120);
        $process = new Process([$pdftotext, '-layout', '-enc', 'UTF-8', $absolutePath, '-']);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new ExtractionException(
                ErrorCode::Timeout,
                'pdftotext excedió el tiempo límite configurado.',
                previous: $e,
            );
        }

        if (! $process->isSuccessful()) {
            throw new ExtractionException(
                ErrorCode::CorruptedFile,
                'pdftotext falló al procesar el PDF: '.trim($process->getErrorOutput() ?: $process->getOutput()),
            );
        }

        $content = $this->normalizer->normalize($process->getOutput());
        $pagesTotal = $this->detectPageCount($absolutePath, $timeout);

        return new ExtractionResultData(
            content: $content,
            driver: ExtractorDriver::PdfNative,
            outputFormat: OutputFormat::PlainText,
            ocrUsed: false,
            pagesTotal: $pagesTotal,
            pagesOcr: 0,
            headingsCount: $this->normalizer->headingsCount($content),
            tablesCount: $this->normalizer->tablesCount($content),
        );
    }

    private function detectPageCount(string $absolutePath, int $timeout): ?int
    {
        $pdfinfo = (string) config('document-extractor.binaries.pdfinfo', '/usr/bin/pdfinfo');

        if (! is_executable($pdfinfo)) {
            return null;
        }

        $process = new Process([$pdfinfo, $absolutePath]);
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        if (preg_match('/^Pages:\s+(\d+)/m', $process->getOutput(), $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
