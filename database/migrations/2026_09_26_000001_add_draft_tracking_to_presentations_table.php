<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            // The outline is stored so a failed draft can be retried without the
            // user re-typing it, and so a stalled generation is inspectable.
            $table->longText('draft_plan')->nullable()->after('source');
            $table->timestamp('draft_requested_at')->nullable()->after('draft_plan');
            $table->timestamp('draft_completed_at')->nullable()->after('draft_requested_at');
            $table->timestamp('draft_failed_at')->nullable()->after('draft_completed_at');
            $table->text('draft_error')->nullable()->after('draft_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropColumn([
                'draft_plan',
                'draft_requested_at',
                'draft_completed_at',
                'draft_failed_at',
                'draft_error',
            ]);
        });
    }
};
