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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('medical_history_id')->nullable()->constrained('medical_history')->nullOnDelete();
            $table->string('medication_name', 200);
            $table->string('dosage', 100);
            $table->string('frequency', 100) ;
            $table->string('duration', 100);
            $table->text('instructions')->nullable();
            $table->date('prescribed_date');
            $table->date('refill_date')->nullable();
            $table->integer('refills_allowed')->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled', 'expired'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'prescribed_date']);
            $table->index('medication_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_prescriptions');
    }
};