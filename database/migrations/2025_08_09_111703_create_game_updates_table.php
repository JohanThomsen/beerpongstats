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
        Schema::create('game_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('type', ['START', 'END', 'MISS', 'EDGE', 'HIT', 'RERACK']);
            $table->json('self_cup_positions')->nullable();
            $table->json('opponent_cup_positions')->nullable();
            $table->integer('self_cups_left');
            $table->integer('opponent_cups_left');
            $table->integer('affected_cup')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_updates');
    }
};
