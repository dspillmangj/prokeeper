<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('default_jersey_number', 5)->nullable();
            $table->string('position', 10)->nullable(); // PG, SG, SF, PF, C, or OH, MB, S, OPP, L, DS
            $table->string('height', 10)->nullable(); // e.g. 6'2"
            $table->string('year_grade', 15)->nullable(); // FR, SO, JR, SR, 9th, 10th...
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
