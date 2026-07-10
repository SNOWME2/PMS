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
        Schema::create('user_data', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Address fields
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();

            //Profile
            
            $table->string('first_name')->nullable()->index();
            $table->string('last_name')->nullable()->index();
            $table->string('middle_name')->nullable()->index();
            $table->string('phone', 20)->nullable();
           //Email is already in users table, so we won't duplicate it here
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->index();


            // Employment details
            $table->string('job_title')->nullable()->index();
            $table->string('department')->nullable();  // Remove if not needed
            $table->string('employment_status')->nullable()->index(); 
            
            //Profile photo
            $table->string('profile_photo_path')->nullable();
            $table->timestamps();

            //account status Online or offline
            $table->enum('status', ['Active', 'Inactive'])->default('Active')->index();
            $table->date('last_login')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_data');
    }
};
