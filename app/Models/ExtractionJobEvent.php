<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'extraction_job_id',
    'event',
    'payload',
])]
class ExtractionJobEvent extends Model
{
    public const UPDATED_AT = null;

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
            'payload' => 'array',
        ];
    }
}
