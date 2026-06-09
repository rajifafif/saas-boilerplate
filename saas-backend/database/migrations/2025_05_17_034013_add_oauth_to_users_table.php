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
        Schema::table('users', function (Blueprint $table) {
            // Make password nullable for OAuth users
            $table->string('password')->nullable()->change();

            // Add auth_provider to track origin (default to 'email')
            $table->string('auth_provider')->default('email')->after('password');

            // Optionally add origin_data json column for extra metadata
            $table->json('origin_data')->nullable()->after('auth_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();

            $table->dropColumn('auth_provider');
            $table->dropColumn('origin_data');
        });
    }
};
