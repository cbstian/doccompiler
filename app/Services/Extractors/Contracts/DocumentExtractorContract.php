<?php

namespace App\Services\Extractors\Contracts;

use App\Services\Extractors\Data\ExtractionResultData;

interface DocumentExtractorContract
{
    /**
     * Extract text from a file on disk.
     *
     * @throws \App\Exceptions\ExtractionException
     */
    public function extract(string $absolutePath): ExtractionResultData;
}
