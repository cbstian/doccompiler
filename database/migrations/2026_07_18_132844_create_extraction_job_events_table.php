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
        Schema::create('extraction_job_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extraction_job_id')->constrained()->cascadeOnDelete();
            $table->string('event', 40);
            $table->json('payload')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_job_events');
    }
};
