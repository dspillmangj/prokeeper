<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('team_side'); // home, away
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('jersey_number', 5);
            $table->string('player_name');
            $table->string('position', 10)->nullable();
            $table->boolean('is_starter')->default(false);
            $table->boolean('is_on_court')->default(false);
            $table->integer('court_position')->nullable(); // 1-5 for basketball, 1-6 for volleyball
            $table->boolean('is_libero')->default(false);
            $table->integer('seconds_played')->default(0);
            $table->integer('subbed_in_at_clock')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'team_side', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_lineups');
    }
};
