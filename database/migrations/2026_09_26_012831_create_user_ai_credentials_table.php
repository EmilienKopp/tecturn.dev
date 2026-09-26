<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user "bring your own AI" credentials for deck generation. The API key is
 * stored encrypted (Eloquent `encrypted` cast, backed by APP_KEY); the plaintext
 * never leaves the row. `driver` is a Laravel AI provider name (Lab enum value)
 * and `model` the provider-specific model id, either curated or user free-texted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ai_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('driver');
            $table->string('model');
            $table->string('base_url')
                ->nullable()
                ->comment('Optional custom endpoint for the provider, e.g. for the openai-compatible driver');
            $table->text('api_key');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_credentials');
    }
};
