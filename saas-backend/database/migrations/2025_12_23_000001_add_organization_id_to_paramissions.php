<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        // 1. Roles Table
        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey, $tableNames) {
            if (!Schema::hasColumn($tableNames['roles'], $teamForeignKey)) {
                $table->foreignUlid($teamForeignKey)->nullable()->after('id')->index();
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique([$teamForeignKey, 'name', 'guard_name']);
            }
        });

        // 2. Model Has Roles
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey, $tableNames) {
            if (!Schema::hasColumn($tableNames['model_has_roles'], $teamForeignKey)) {
                $table->foreignUlid($teamForeignKey)->nullable()->index();
                $table->dropPrimary('model_has_roles_role_model_type_primary');
                $table->primary([$teamForeignKey, 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary_custom');
            }
        });

        // 3. Model Has Permissions
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey, $tableNames) {
            if (!Schema::hasColumn($tableNames['model_has_permissions'], $teamForeignKey)) {
                $table->foreignUlid($teamForeignKey)->nullable()->index();
                $table->dropPrimary('model_has_permissions_permission_model_type_primary');
                $table->primary([$teamForeignKey, 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary_custom');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing this is complex due to index drops, usually implies resetting permissions.
        // For now, we allow standard rollback.
    }
};
