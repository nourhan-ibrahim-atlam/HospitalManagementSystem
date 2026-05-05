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
        Schema::create('blood_test_parameters', function (Blueprint $table) {
           $table->id();
            $table->foreignId('lab_test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->string('parameter_name', 100);
            $table->decimal('value', 10, 2);
            $table->string('unit', 50);
            $table->string('reference_range', 100);
            $table->string('flag', 20)->nullable(); 
            $table->timestamps();

            $table->index('parameter_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_test_parameters');
    }
};
