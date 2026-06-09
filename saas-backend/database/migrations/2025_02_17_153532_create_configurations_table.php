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
        Schema::create('configurations', function (Blueprint $table) {
            $table->ulid('id')->primary(); // ULID as primary key
            $table->ulidMorphs('configurable'); // Polymorphic relationship
            $table->string('key')->index(); // Configuration key
            $table->text('value')->nullable(); // Configuration value (JSON for flexibility)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
