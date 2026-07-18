<?php

namespace App\Models;

use App\Enums\ErrorCode;
use App\Enums\ExtractorDriver;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'uuid',
    'api_client_id',
    'original_filename',
    'extension',
    'declared_mime_type',
    'detected_mime_type',
    'size_bytes',
    'storage_path',
    'status',
    'extractor_driver',
    'ocr_used',
    'pages_total',
    'pages_ocr',
    'error_code',
    'error_message',
    'started_at',
    'completed_at',
    'duration_ms',
])]
class ExtractionJob extends Model
{
    protected $attributes = [
        'status' => 'pending',
        'ocr_used' => false,
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ExtractionResult::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ExtractionChunk::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExtractionJobEvent::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Pending);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Processing);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Completed);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Failed);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'extractor_driver' => ExtractorDriver::class,
            'error_code' => ErrorCode::class,
            'ocr_used' => 'boolean',
            'size_bytes' => 'integer',
            'pages_total' => 'integer',
            'pages_ocr' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
