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
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('medical_history_id')->nullable()->constrained('medical_history')->nullOnDelete();
            $table->string('test_name', 200);
            $table->string('test_category', 100);
            $table->date('test_date');
            $table->date('result_date')->nullable();
            $table->text('results')->nullable();
            $table->text('reference_range')->nullable();
            $table->text('interpretation')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->string('file_path')->nullable();
            $table->text('technician_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'test_date']);
            $table->index('test_category');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test');
    }
};
