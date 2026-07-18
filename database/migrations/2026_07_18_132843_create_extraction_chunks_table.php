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
        Schema::create('extraction_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extraction_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->mediumText('content');
            $table->unsignedInteger('token_count');
            $table->timestamps();

            $table->index(['extraction_job_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_chunks');
    }
};
