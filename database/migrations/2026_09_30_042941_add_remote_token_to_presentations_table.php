<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            // Secret pairing the presenter's phone remote, sibling of
            // embed_token but never shown to the audience: the QR only appears
            // in the (unprojected) editor.
            $table->string('remote_token', 64)->nullable()->unique()->after('embed_token');
        });

        // Existing decks get theirs here; new ones on create, like embed_token.
        DB::table('presentations')->whereNull('remote_token')->pluck('id')
            ->each(function (int $id) {
                DB::table('presentations')->where('id', $id)->update([
                    'remote_token' => Str::random(32),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropColumn('remote_token');
        });
    }
};
