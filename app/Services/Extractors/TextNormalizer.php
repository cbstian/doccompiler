<?php

namespace App\Services\Extractors;

class TextNormalizer
{
    /**
     * Collapse redundant whitespace while preserving intentional blank lines.
     */
    public function normalize(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace("/[ \t]+/u", ' ', $content) ?? $content;
        $content = preg_replace("/\n{3,}/u", "\n\n", $content) ?? $content;

        return trim($content)."\n";
    }

    public function charCount(string $content): int
    {
        return mb_strlen($content);
    }

    /**
     * Rough token estimate (~4 chars per token). Good enough for capacity planning.
     */
    public function estimatedTokens(string $content): int
    {
        $chars = $this->charCount($content);

        return $chars === 0 ? 0 : (int) ceil($chars / 4);
    }

    public function headingsCount(string $content): int
    {
        return preg_match_all('/^#{1,6}\s+\S/m', $content) ?: 0;
    }

    public function tablesCount(string $content): int
    {
        return preg_match_all('/^\|.+\|$/m', $content) ?: 0;
    }
}
