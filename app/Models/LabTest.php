<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabTest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lab_tests';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'medical_history_id',
        'test_name',
        'test_category',
        'test_date',
        'result_date',
        'results',
        'reference_range',
        'interpretation',
        'status',
        'file_path',
        'technician_notes',
    ];

    protected $casts = [
        'test_date' => 'date',
        'result_date' => 'date',
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

    public function bloodTestParameters()
    {
        return $this->hasMany(BloodTestParameter::class);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
