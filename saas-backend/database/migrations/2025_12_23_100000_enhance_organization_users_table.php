<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Enhances the organization_users pivot table with:
     * - is_default flag for default organization selection
     * - joined_at timestamp for membership tracking
     * - Proper unique constraint
     */
    public function up(): void
    {
        Schema::table('organization_users', function (Blueprint $table) {
            // Add new columns
            $table->boolean('is_default')->default(false)->after('role');
            $table->timestamp('joined_at')->nullable()->after('is_default');
        });

        // Set joined_at for existing records
        DB::table('organization_users')
            ->whereNull('joined_at')
            ->update(['joined_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_users', function (Blueprint $table) {
            $table->dropColumn(['is_default', 'joined_at']);
        });
    }
};
