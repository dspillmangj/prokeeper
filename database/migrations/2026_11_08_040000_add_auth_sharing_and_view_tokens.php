<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 2FA and plan fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_email_enabled')->default(false)->after('role');
            $table->string('two_factor_code')->nullable()->after('two_factor_email_enabled');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
            $table->string('plan')->default('free')->after('two_factor_expires_at'); // free, pro
        });

        // 2. Add plan to organizations
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('slug'); // free, enterprise
            $table->unsignedInteger('seats')->default(1)->after('plan');
            $table->foreignId('billing_user_id')->nullable()->after('seats')->constrained('users')->nullOnDelete();
        });

        // 3. Add secure view tokens to games
        Schema::table('games', function (Blueprint $table) {
            $table->string('scorebook_token', 64)->nullable()->after('access_code');
            $table->string('stats_token', 64)->nullable()->after('scorebook_token');
        });

        // 4. Create roster_shares table
        Schema::create('roster_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('shared_with_email');
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('permission')->default('view'); // view, edit
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['team_id', 'shared_with_email']);
            $table->index('shared_with_user_id');
        });

        // 5. Create game_shares table
        Schema::create('game_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('shared_with_email');
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('permission')->default('view'); // view, edit
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['game_id', 'shared_with_email']);
            $table->index('shared_with_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_shares');
        Schema::dropIfExists('roster_shares');

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['scorebook_token', 'stats_token']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['billing_user_id']);
            $table->dropColumn(['plan', 'seats', 'billing_user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_email_enabled', 'two_factor_code', 'two_factor_expires_at', 'plan']);
        });
    }
};

