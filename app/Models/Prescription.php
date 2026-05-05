<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prescriptions';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'medical_history_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'prescribed_date',
        'refill_date',
        'refills_allowed',
        'status',
    ];

    protected $casts = [
        'prescribed_date' => 'date',
        'refill_date' => 'date',
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

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function needsRefill()
    {
        return $this->refill_date && $this->refill_date <= now() && $this->refills_allowed > 0;
    }
}
