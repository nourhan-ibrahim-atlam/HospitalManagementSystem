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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->enum('specialization', [
                'General Practitioner',
                'Cardiology',
                'Dermatology',
                'Neurology',
                'Pediatrics',
                'Orthopedics',
                'Gynecology',
                'Ophthalmology',
                'ENT',
                'Urology',
                'Psychiatry',
                'Oncology',
                'Radiology',
                'Anesthesiology',
                'Gastroenterology',
                'Endocrinology',
                'Pulmonology',
                'Nephrology',
                'Hematology',
                'Infectious Diseases',
                'Rheumatology',
                'Plastic Surgery',
                'Emergency Medicine',
                'Family Medicine'
            ]);

            // Document uploads
            $table->string('medical_license')->nullable(); // Path to license file
            $table->string('degree_certificate')->nullable(); // Path to degree certificate
            $table->string('professional_id_card')->nullable(); // Path to ID card

            // Simple verification
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('specialization');
            $table->index('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
