<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\ApiClient;
use App\Models\ExtractionJob;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExtractionJob>
 */
class ExtractionJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'api_client_id' => ApiClient::factory(),
            'original_filename' => fake()->word().'.txt',
            'extension' => 'txt',
            'declared_mime_type' => 'text/plain',
            'detected_mime_type' => 'text/plain',
            'size_bytes' => 12,
            'storage_path' => null,
            'status' => JobStatus::Pending,
            'ocr_used' => false,
        ];
    }
}
