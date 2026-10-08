<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('access_code', 10)->unique(); // e.g. "EAG789"
            $table->string('slug')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('sport')->default('basketball'); // basketball, volleyball
            $table->string('status')->default('scheduled'); // scheduled, in_progress, paused, final

            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('home_team_name')->nullable();
            $table->string('home_team_score_color')->default('#1e40af');

            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('away_team_name')->nullable();
            $table->string('away_team_score_color')->default('#b91c1c');

            $table->integer('current_period')->default(1);
            $table->integer('clock_seconds_remaining')->default(480); // default 8:00
            $table->boolean('clock_running')->default(false);
            $table->timestamp('clock_last_started_at')->nullable();

            $table->integer('home_score')->default(0);
            $table->integer('away_score')->default(0);
            $table->json('home_period_scores')->nullable();
            $table->json('away_period_scores')->nullable();

            $table->integer('home_timeouts_remaining')->default(5);
            $table->integer('away_timeouts_remaining')->default(5);

            $table->integer('home_fouls_current_period')->default(0);
            $table->integer('away_fouls_current_period')->default(0);

            $table->string('possession_arrow')->default('home'); // home, away
            $table->string('current_server')->default('home'); // home, away (for volleyball)
            $table->integer('home_rotation')->default(1); // 1-6
            $table->integer('away_rotation')->default(1); // 1-6

            $table->string('venue')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->json('settings')->nullable(); // period length, foul bonus thresholds, sets to win, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
