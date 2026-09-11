<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            // Languages the presenter wants captions for. YoYoTranslate dropped
            // the `lang=all` wildcard, so the socket now needs explicit codes.
            $table->json('yoyotranslate_languages')->nullable()->after('yoyotranslate_session_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropColumn('yoyotranslate_languages');
        });
    }
};
