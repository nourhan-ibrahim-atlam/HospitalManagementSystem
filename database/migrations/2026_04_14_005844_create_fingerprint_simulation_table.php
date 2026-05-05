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
        Schema::create('fingerprint_simulation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                  ->unique()
                  ->constrained('patients')
                  ->cascadeOnDelete();
            $table->string('fingerprint_code', 32)->unique();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerprint_simulation');
    }
};
