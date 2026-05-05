<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Immunization extends Model
{
    use HasFactory;

    protected $table = 'immunizations';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'vaccine_name',
        'administration_date',
        'next_dose_date',
        'lot_number',
        'manufacturer',
        'notes',
    ];

    protected $casts = [
        'administration_date' => 'date',
        'next_dose_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function isDueForNextDose()
    {
        return $this->next_dose_date && $this->next_dose_date <= now();
    }
}
