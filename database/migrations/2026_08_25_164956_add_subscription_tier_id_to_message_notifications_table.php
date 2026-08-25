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
        Schema::table('message_notifications', function (Blueprint $table) {
            $table->foreignId('subscription_tier_id')->nullable()->after('message_type')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_tier_id');
        });
    }
};
