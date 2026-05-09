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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->string('name');          // e.g. Sunview Apartments
            $table->string('address');       // location
            $table->text('description')->nullable();
            $table->string('city');
            $table->string('type');
            $table->int('total_floors');
            $table->string('property_image');
            $table->string('status');
            $table->foreignId('created_by')->constrained('user_id')->cascadeOnDelete();
            $table->timestamps();
        });
        //Pivot table for property amenities
        Schema::create('property_amneties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('amenity_id')
                ->constrained('amneties')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
