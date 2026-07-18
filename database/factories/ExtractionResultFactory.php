<?php

namespace Database\Factories;

use App\Enums\OutputFormat;
use App\Models\ExtractionJob;
use App\Models\ExtractionResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtractionResult>
 */
class ExtractionResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'extraction_job_id' => ExtractionJob::factory(),
            'output_format' => OutputFormat::PlainText,
            'content' => fake()->paragraph(),
            'content_disk' => null,
            'content_path' => null,
            'char_count' => 120,
            'estimated_tokens' => 30,
            'headings_count' => 0,
            'tables_count' => 0,
            'language_detected' => 'es',
        ];
    }
}
