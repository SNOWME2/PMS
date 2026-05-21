<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── maintenance_requests ──────────────────────────────────────────────
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->enum('category', [
                'plumbing',
                'electrical',
                'hvac',
                'appliance',
                'paint',
                'cleaning',
                'structural',
                'other',
            ])->default('other');

            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', [
                'open',
                'assigned',
                'in-progress',
                'completed',
                'cancelled',
            ])->default('open');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();

            $table->json('photos_before')->nullable();  // array of storage paths
            $table->json('photos_after')->nullable();

            $table->text('notes')->nullable();          // tenant-visible notes
            $table->text('internal_notes')->nullable();  // admin-only notes

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index('assigned_to');
            $table->index('created_at');
            $table->index('unit_id');
        });

        // ── maintenance_updates (audit trail) ─────────────────────────────────
        Schema::create('maintenance_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', [
                'status_change',
                'assigned',
                'started',
                'completed',
                'cancelled',
                'note_added',
                'photo_added',
                'cost_updated',
                'other',
            ])->default('other');

            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamp('created_at');

            $table->index('maintenance_request_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_updates');
        Schema::dropIfExists('maintenance_requests');
    }
};
