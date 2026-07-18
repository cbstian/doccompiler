<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'extraction_job_id',
    'chunk_index',
    'content',
    'token_count',
])]
class ExtractionChunk extends Model
{
    public function extractionJob(): BelongsTo
    {
        return $this->belongsTo(ExtractionJob::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
        ];
    }
}
