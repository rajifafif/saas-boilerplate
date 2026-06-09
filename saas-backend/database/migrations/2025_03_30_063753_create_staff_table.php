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
        Schema::create('staff', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->index();
            $table->foreignUlid('user_id')->index()->nullable();
            $table->foreignUlid('person_id')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignUlid('created_by')->nullable()->index();
            $table->foreignUlid('updated_by')->nullable()->index();
            $table->foreignUlid('deleted_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['organization_id', 'user_id', 'person_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
