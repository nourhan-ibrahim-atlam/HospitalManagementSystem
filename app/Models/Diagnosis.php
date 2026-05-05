<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    protected $table = 'diagnoses';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'medical_history_id',
        'icd_code',
        'diagnosis_name',
        'description',
        'certainty',
        'diagnosis_date',
        'notes',
    ];

    protected $casts = [
        'diagnosis_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function medicalHistory()
    {
        return $this->belongsTo(MedicalHistory::class);
    }

    public function isConfirmed()
    {
        return $this->certainty === 'confirmed';
    }
}
