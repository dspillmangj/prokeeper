<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basketball_stats', function (Blueprint $table) {
            $table->integer('fouls_flagrant')->default(0)->after('fouls_tech');
        });
    }

    public function down(): void
    {
        Schema::table('basketball_stats', function (Blueprint $table) {
            $table->dropColumn('fouls_flagrant');
        });
    }
};
