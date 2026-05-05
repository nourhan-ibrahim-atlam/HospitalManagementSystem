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
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('medical_history_id')->constrained('medical_history')->cascadeOnDelete();
            $table->string('icd_code', 20)->nullable();
            $table->string('diagnosis_name', 200);
            $table->text('description')->nullable();
            $table->enum('certainty', ['confirmed', 'probable', 'possible', 'ruled_out']);
            $table->date('diagnosis_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'diagnosis_date']);
            $table->index('icd_code');
            $table->index('diagnosis_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
