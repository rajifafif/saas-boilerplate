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
        Schema::create('addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type')->nullable(); // e.g., 'home', 'office', 'shipping'
            $table->string('name')->nullable();

            // Location hierarchy
            $table->string('provinsi_id')->nullable();
            $table->string('kota_id')->nullable();
            $table->string('kecamatan_id')->nullable();
            $table->string('kelurahan_id')->nullable();
            $table->string('kode_pos')->nullable();

            $table->text('text')->nullable(); // Full address text
            $table->text('description')->nullable(); // Additional details

            // Coordinates
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Receiver info (if used for shipping)
            $table->string('full_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();

            // Polymorphic relation
            $table->ulidMorphs('addressable');

            // Audit trails
            $table->ulid('created_by')->nullable();
            $table->ulid('updated_by')->nullable();
            $table->ulid('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
