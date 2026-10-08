<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->integer('sequence')->default(1);
            $table->integer('period')->default(1);
            $table->integer('clock_seconds_remaining')->default(0);
            $table->string('team_side'); // home, away
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('jersey_number', 5)->nullable();
            $table->string('player_name')->nullable();

            $table->foreignId('assist_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('assist_jersey_number', 5)->nullable();

            $table->string('sport')->default('basketball');
            $table->string('action_code', 10); // X, Z, M, N, B, V, D, O, F, R, T, H, A, S, W, K, P, U, I, or VB: K, E, T, A, S, D, B, C, H, R
            $table->string('action_type', 30); // e.g. 2pt_make, def_reb, assist, kill, etc.
            $table->string('action_name'); // e.g. "2pt MAKE", "Def Reb", "Kill"
            $table->string('raw_input', 30)->nullable(); // e.g. "23-X", "11=M"

            $table->integer('points')->default(0);
            $table->integer('home_score_after')->default(0);
            $table->integer('away_score_after')->default(0);

            $table->boolean('is_undone')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'sequence']);
            $table->index(['game_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
