<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── tenants ───────────────────────────────────────────────────────────
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20);
            $table->enum('id_type', ['national_id', 'passport', 'driver_license'])->nullable();
            $table->string('id_number')->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('email');
        });

        // ── leases ────────────────────────────────────────────────────────────
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('rent_price', 12, 2);           // monthly rent
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->enum('status', ['active', 'expired', 'terminated'])->default('active');

            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('tenant_id');
            $table->index('unit_id');
        });

        // ── payments ──────────────────────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();   // 'cash', 'check', 'bank_transfer', 'gcash', 'maya'
            $table->string('reference_number')->nullable(); // check/receipt number
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index('payment_date');
            $table->index('lease_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('leases');
        Schema::dropIfExists('tenants');
    }
};
