<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "LBS Eagles Varsity"
            $table->string('short_name', 10)->nullable(); // e.g. "EAGLES"
            $table->string('sport'); // basketball, volleyball
            $table->string('gender')->default('boys'); // boys, girls, coed
            $table->string('level')->default('varsity'); // varsity, jv, freshman, middle_school, club
            $table->string('season')->default('2026-2027');
            $table->string('home_jersey_color')->default('#ffffff');
            $table->string('away_jersey_color')->default('#0f172a');
            $table->string('logo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
