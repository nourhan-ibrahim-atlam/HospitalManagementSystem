<?php
// database/migrations/2026_01_22_000003_create_complete_update_requests_table.php

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
        // First drop the existing table if it exists
        Schema::dropIfExists('update_requests');

        // Create new table with complete structure
        Schema::create('update_requests', function (Blueprint $table) {
            $table->id();

            // Target information (who is being updated)
            $table->enum('target_type', ['patient', 'doctor'])
                  ->nullable()
                  ->comment('Type of target being updated: patient or doctor');
            $table->unsignedBigInteger('target_id')
                  ->nullable()
                  ->comment('ID of the target being updated');

            // Foreign keys for direct relationships
            $table->foreignId('patient_id')
                  ->nullable()
                  ->constrained('patients')
                  ->nullOnDelete()
                  ->comment('Patient ID if target is patient');

            $table->foreignId('doctor_id')
                  ->nullable()
                  ->constrained('doctors')
                  ->nullOnDelete()
                  ->comment('Doctor ID if target is doctor');

            // Requester information
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who requested the update');

            $table->enum('requester_type', ['patient', 'doctor', 'admin'])
                  ->nullable()
                  ->comment('Role of the requester');

            // Update details
            $table->string('field_name')
                  ->comment('Name of the field being updated');
            $table->text('old_value')
                  ->nullable()
                  ->comment('Current value before update');
            $table->text('new_value')
                  ->comment('Requested new value');
            $table->text('reason')
                  ->nullable()
                  ->comment('Reason for the update request');

            // Status tracking
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                  ->default('pending')
                  ->comment('Current status of the request');

            // Review information
            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Admin who reviewed the request');
            $table->timestamp('reviewed_at')
                  ->nullable()
                  ->comment('When the request was reviewed');
            $table->text('reviewer_notes')
                  ->nullable()
                  ->comment('Notes from the reviewer');

            // Cancellation information
            $table->foreignId('cancelled_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('User who cancelled the request');
            $table->timestamp('cancelled_at')
                  ->nullable()
                  ->comment('When the request was cancelled');
            $table->text('cancellation_reason')
                  ->nullable()
                  ->comment('Reason for cancellation');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['target_type', 'target_id']);
            $table->index(['target_type', 'target_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['requester_type', 'status']);
            $table->index('field_name');
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');

            // Composite indexes for common queries
            $table->index(['status', 'created_at']);
            $table->index(['target_type', 'status', 'created_at']);
            $table->index(['requester_type', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_requests');
    }
};
