<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('jersey_number', 5);
            $table->string('position', 10)->nullable();
            $table->boolean('is_starter')->default(false);
            $table->boolean('is_libero')->default(false);
            $table->boolean('is_captain')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_players');
    }
};
