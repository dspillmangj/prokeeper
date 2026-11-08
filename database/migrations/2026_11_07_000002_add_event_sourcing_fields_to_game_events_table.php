<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_events', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->index();
            $table->timestamp('wall_clock_time')->nullable()->after('clock_seconds_remaining');
            $table->string('possession_arrow_after', 10)->nullable()->after('away_score_after');
            $table->integer('home_fouls_after')->default(0)->after('possession_arrow_after');
            $table->integer('away_fouls_after')->default(0)->after('home_fouls_after');
            $table->integer('home_timeouts_after')->default(5)->after('away_fouls_after');
            $table->integer('away_timeouts_after')->default(5)->after('home_timeouts_after');
            $table->foreignId('actor_id')->nullable()->after('metadata')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_events', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->dropColumn([
                'uuid',
                'wall_clock_time',
                'possession_arrow_after',
                'home_fouls_after',
                'away_fouls_after',
                'home_timeouts_after',
                'away_timeouts_after',
                'actor_id',
            ]);
        });
    }
};

