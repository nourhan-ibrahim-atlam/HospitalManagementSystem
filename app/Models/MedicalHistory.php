<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medical_history';

protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_date',
        'chief_complaint',
        'present_illness_history',
        'past_medical_history',
        'family_history',
        'social_history',
        'allergies',
        'current_medications',
        'physical_examination',
        'diagnosis',
        'treatment_plan',
        'doctor_notes',
        'visit_type',
        'status',
    ];
     protected $casts = [
        'visit_date' => 'date',
    ];

       public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function labTests()
    {
        return $this->hasMany(LabTest::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class);
    }


    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class);
    }

    public function scopeActive($query){
        return $query->where('status' , 'active') ;
    }

}