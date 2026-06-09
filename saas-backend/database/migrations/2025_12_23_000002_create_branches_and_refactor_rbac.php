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
        // 1. Create Branches Table
        Schema::create('branches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable(); // Short code for the branch
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            // $table->ulidMorphs('addressable'); // Optional address
            $table->timestamps();
            $table->softDeletes();
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->ulid('deleted_by')->nullable();
        });

        // 2. RBAC Refactoring (Hybrid)
        // Roles belong to Organization (Dynamic Definition)
        // Assignments (model_has_roles) belong to Branch (Contextual Access)

        $permConfig = config('permission.table_names');

        // A. Add organization_id to ROLES table
        Schema::table($permConfig['roles'], function (Blueprint $table) {
            $table->foreignUlid('organization_id')->nullable()->after('id')->index();
        });

        // B. Add branch_id to MODEL_HAS_ROLES table (This is the Team Key used by Spatie)
        // Note: The 'team_foreign_key' config should point to 'branch_id'.
        // We ensure the column exists. If previous migration added it as organization_id or team_id, we standardize.
        // Assuming fresh install or previous state: verify column

        Schema::table($permConfig['model_has_roles'], function (Blueprint $table) use ($permConfig) {
            if (!Schema::hasColumn($permConfig['model_has_roles'], 'branch_id')) {
                $table->foreignUlid('branch_id')->nullable()->index();

                // We might need to drop old primary/unique keys if they used organization_id
                // But for now, let's assume we are adding the new context column.
                // Spatie checks config('permission.column_names.team_foreign_key')
            }
        });

        Schema::table($permConfig['model_has_permissions'], function (Blueprint $table) use ($permConfig) {
            if (!Schema::hasColumn($permConfig['model_has_permissions'], 'branch_id')) {
                $table->foreignUlid('branch_id')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
