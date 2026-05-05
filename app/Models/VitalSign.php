<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    use HasFactory;

    protected $table = 'vital_signs';

    protected $fillable = [
        'patient_id',
        'medical_history_id',
        'temperature',
        'heart_rate',
        'respiratory_rate',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'oxygen_saturation',
        'height',
        'weight',
        'bmi',
        'blood_glucose',
        'measured_at',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medicalHistory()
    {
        return $this->belongsTo(MedicalHistory::class);
    }

    public function getBloodPressureAttribute()
    {
        return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
    }

    public function calculateBMI()
    {
        if ($this->height && $this->weight) {
            return round($this->weight / (($this->height / 100) ** 2), 2);
        }
        return null;
    }

    public function isHypertensive()
    {
        return $this->blood_pressure_systolic >= 140 || $this->blood_pressure_diastolic >= 90;
    }
}
