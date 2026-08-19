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
        Schema::create('treasure_hunts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('treasure_id')->constrained();
            $table->enum('status', ['hidden', 'found'])->default('hidden');
            $table->dateTime('found_at')->nullable();
            $table->enum('reward_status', ['pending', 'rewarded'])->default('pending');
            $table->string('reward')->nullable();
            $table->dateTime('rewarded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'treasure_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasure_hunts');
    }
};
