<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('type', 20)->default('page');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->string('permission_name')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
