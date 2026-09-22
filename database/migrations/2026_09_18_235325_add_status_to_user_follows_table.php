<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follows become requests: new rows start as "pending" until the followed
     * user accepts. Existing rows predate the request flow and stay accepted.
     */
    public function up(): void
    {
        Schema::table('user_follows', function (Blueprint $table) {
            $table->string('status')->default('accepted')->after('followed_user_id');
            $table->index(['followed_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('user_follows', function (Blueprint $table) {
            $table->dropIndex(['followed_user_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
