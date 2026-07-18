<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('extraction_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extraction_job_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('output_format', 20);
            $table->longText('content')->nullable();
            $table->string('content_disk', 20)->nullable();
            $table->string('content_path')->nullable();
            $table->unsignedInteger('char_count');
            $table->unsignedInteger('estimated_tokens');
            $table->unsignedSmallInteger('headings_count')->default(0);
            $table->unsignedSmallInteger('tables_count')->default(0);
            $table->string('language_detected', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_results');
    }
};
