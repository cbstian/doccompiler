<?php

namespace App\Models;

use App\Enums\OutputFormat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'extraction_job_id',
    'output_format',
    'content',
    'content_disk',
    'content_path',
    'char_count',
    'estimated_tokens',
    'headings_count',
    'tables_count',
    'language_detected',
])]
class ExtractionResult extends Model
{
    public function extractionJob(): BelongsTo
    {
        return $this->belongsTo(ExtractionJob::class);
    }

    public function isStoredOnDisk(): bool
    {
        return $this->content === null && $this->content_path !== null;
    }

    public function isStoredInDatabase(): bool
    {
        return $this->content !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'output_format' => OutputFormat::class,
            'char_count' => 'integer',
            'estimated_tokens' => 'integer',
            'headings_count' => 'integer',
            'tables_count' => 'integer',
        ];
    }
}
