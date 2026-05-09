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
        Schema::create('amneties', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Swimming Pool, Gym, Parking, etc.
            $table->string('icon'); // e.g. FontAwesome class for the icon
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amneties');
    }
};
