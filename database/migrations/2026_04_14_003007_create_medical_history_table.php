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
        Schema::create('medical_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->cascadeOnDelete();
             $table->foreignId('doctor_id')
             ->constrained('doctors')
             ->cascadeOnDelete();
            $table->date('visit_date');
            $table->text('chief_complaint');
            $table->text('present_illness_history');
            $table->text('past_medical_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('physical_examination')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->enum('visit_type', ['initial', 'follow_up', 'emergency', 'consultation']);
            $table->enum('status', ['active', 'resolved', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'visit_date']);
            $table->index('doctor_id');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_history');
    }
};
