<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            // Null until the deck joins a talk: versioning is opt-in and the
            // talk is created lazily on the first "new version" action.
            // Deliberately no FK constraint: adding one to an existing table
            // rewrites it on sqlite, which fails while DB views reference it.
            $table->unsignedBigInteger('talk_id')->nullable();
            $table->unsignedInteger('version_major')->nullable();
            $table->unsignedInteger('version_minor')->nullable();

            $table->index(['talk_id', 'version_major', 'version_minor']);
        });

        Schema::table('practice_runs', function (Blueprint $table) {
            // The deck's version at the moment of the run, so a snapshot knows
            // what it was derived from. Null for unversioned decks.
            $table->unsignedInteger('version_major')->nullable();
            $table->unsignedInteger('version_minor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('practice_runs', function (Blueprint $table) {
            $table->dropColumn(['version_major', 'version_minor']);
        });

        Schema::table('presentations', function (Blueprint $table) {
            $table->dropIndex(['talk_id', 'version_major', 'version_minor']);
            $table->dropColumn(['talk_id', 'version_major', 'version_minor']);
        });
    }
};
