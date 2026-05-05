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
        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->cascadeOnDelete();
            $table->foreignId('doctor_id')
                  ->constrained('doctors')
                  ->cascadeOnDelete();
            $table->dateTime('visit_time');
            $table->text('notes')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'doctor_id']);
            $table->index('visit_time');
            $table->index('severity');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_visit_');
    }
};