<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basketball_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('team_side'); // home, away
            $table->string('jersey_number', 5);
            $table->string('player_name');

            // Scoring
            $table->integer('points')->default(0);
            $table->integer('fgm')->default(0);
            $table->integer('fga')->default(0);
            $table->integer('fg3m')->default(0);
            $table->integer('fg3a')->default(0);
            $table->integer('ftm')->default(0);
            $table->integer('fta')->default(0);

            // Rebounds
            $table->integer('oreb')->default(0);
            $table->integer('dreb')->default(0);
            $table->integer('reb')->default(0);

            // Playmaking & Defense
            $table->integer('ast')->default(0);
            $table->integer('stl')->default(0);
            $table->integer('blk')->default(0); // blocks
            $table->integer('swat')->default(0); // swats / deflections

            // Turnovers
            $table->integer('to_pass')->default(0);
            $table->integer('to_fumble')->default(0);
            $table->integer('to_violation')->default(0);
            $table->integer('turnovers')->default(0);

            // Fouls
            $table->integer('fouls_pers')->default(0);
            $table->integer('fouls_off')->default(0);
            $table->integer('fouls_tech')->default(0);
            $table->integer('fouls_forced')->default(0);
            $table->integer('total_fouls')->default(0);

            // Playing time
            $table->integer('seconds_played')->default(0);

            $table->timestamps();

            $table->unique(['game_id', 'team_side', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basketball_stats');
    }
};
