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
        Schema::create('user_habits', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Tracks interactive checkbox for Al-Quran Tadabbur wa Amal');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('sentence_id')->constrained('sentences')->cascadeOnDelete()->comment('References a quranic_action sentence');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('checked_at');
            $table->index('user_id'); // Just explicitly indexing per DBML
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_habits');
    }
};
