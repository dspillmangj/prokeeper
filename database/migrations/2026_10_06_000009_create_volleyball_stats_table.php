<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volleyball_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('team_side'); // home, away
            $table->string('jersey_number', 5);
            $table->string('player_name');

            $table->integer('sets_played')->default(1);
            $table->integer('kills')->default(0);
            $table->integer('attack_errors')->default(0);
            $table->integer('attack_attempts')->default(0);
            $table->decimal('hitting_percentage', 5, 3)->default(0.000);

            $table->integer('assists')->default(0);
            $table->integer('service_aces')->default(0);
            $table->integer('service_errors')->default(0);
            $table->integer('service_attempts')->default(0);

            $table->integer('digs')->default(0);
            $table->integer('block_solos')->default(0);
            $table->integer('block_assists')->default(0);
            $table->decimal('total_blocks', 4, 1)->default(0.0);

            $table->integer('ball_handling_errors')->default(0);
            $table->integer('reception_errors')->default(0);
            $table->decimal('total_points', 4, 1)->default(0.0);

            $table->timestamps();

            $table->unique(['game_id', 'team_side', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volleyball_stats');
    }
};
