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
        Schema::table('presentations', function (Blueprint $table) {
            // Null is treated as an editor-built deck, keeping every existing row
            // backward compatible. External decks (pdf / google_slides) store
            // {"type": ..., "externalUrl": ...} here.
            $table->json('source')->nullable()->after('flow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
