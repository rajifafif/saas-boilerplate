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
        Schema::create('system_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_name'); // Example: "Store Setup", "Import Product"
            $table->string('reference_id')->nullable(); // e.g., store_id, import_id
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'canceled'])
                ->default('pending');
            $table->text('error_message')->nullable();

            // Status timestamps
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps(); // created_at and updated_at
            $table->softDeletes();
            $table->foreignUlid('created_by')->nullable()->index();
            $table->foreignUlid('updated_by')->nullable()->index();
            $table->foreignUlid('deleted_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_processes');
    }
};
