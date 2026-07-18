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
        Schema::create('extraction_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('extension', 10);
            $table->string('declared_mime_type', 100)->nullable();
            $table->string('detected_mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('storage_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('extractor_driver', 20)->nullable();
            $table->boolean('ocr_used')->default(false);
            $table->unsignedInteger('pages_total')->nullable();
            $table->unsignedInteger('pages_ocr')->nullable();
            $table->string('error_code', 40)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['api_client_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_jobs');
    }
};
