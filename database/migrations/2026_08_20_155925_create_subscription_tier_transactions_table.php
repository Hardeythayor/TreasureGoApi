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
        Schema::create('subscription_tier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('user_tier_subscriptions');
            $table->string('transaction_reference');
            $table->string('payment_type')->nullable();
            $table->string('amount');
            $table->enum('status', ['approved', 'pending', 'declined'])->default('pending');
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_tier_transactions');
    }
};
