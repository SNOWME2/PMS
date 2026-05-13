<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════════════════
// Run: php artisan migrate
// ══════════════════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ── amenities ─────────────────────────────────────────────────────────
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();  // lucide icon name
            $table->timestamps();
        });

        // ── properties ────────────────────────────────────────────────────────
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->enum('type', ['residential', 'commercial'])->default('residential');
            $table->text('description')->nullable();
            $table->string('photo')->nullable();           // relative storage path
            $table->unsignedInteger('occupied_units')->default(0);  // cached counter
            $table->timestamps();
            $table->softDeletes();
        });

        // ── property_amenities (pivot) ─────────────────────────────────────────
        Schema::create('property_amenities', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['property_id', 'amenity_id']);
        });

        // ── units ──────────────────────────────────────────────────────────────
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number', 20);             // e.g. "3A", "101"
            $table->enum('type', ['studio', '1BR', '2BR', '3BR', 'penthouse', 'commercial']);
            $table->unsignedSmallInteger('floor_number');
            $table->decimal('size_sqm', 8, 2)->nullable();
            $table->decimal('rent_price', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->enum('status', ['vacant', 'occupied', 'maintenance', 'reserved'])->default('vacant');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // unit_number unique per property (not globally)
            $table->unique(['property_id', 'unit_number']);
        });

        // ── unit_amenities (pivot) ────────────────────────────────────────────
        Schema::create('unit_amenities', function (Blueprint $table) {
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->primary(['unit_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_amenities');
        Schema::dropIfExists('units');
        Schema::dropIfExists('property_amenities');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('amenities');
    }
};
