<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rehearsal_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_run_id')->constrained('practice_runs')->cascadeOnDelete();
            $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['practice_run_id', 'reviewer_user_id']);
            $table->index(['reviewer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rehearsal_reviews');
    }
};
