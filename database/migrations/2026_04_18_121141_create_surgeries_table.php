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
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('surgery_name', 200);
            $table->date('surgery_date');
            $table->string('hospital', 200)->nullable();
            $table->string('surgeon_name', 200)->nullable();
            $table->text('reason')->nullable();
            $table->text('complications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'surgery_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgeries');
    }
};