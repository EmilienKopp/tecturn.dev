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
        Schema::create('beta_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Email is stored encrypted at the application layer, so it lives in
            // a text column. A deterministic sha256 hash gives us a unique index
            // for dedupe without exposing the plaintext address.
            $table->text('email');
            $table->string('email_hash', 64)->unique();
            $table->text('message')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beta_requests');
    }
};
